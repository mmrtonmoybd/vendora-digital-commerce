<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Contracts\CustomerDetails;
use App\Payments\Contracts\InitiationResult;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Contracts\VerificationResult;
use App\Settings\ShurjopaySettings;
use App\Support\Money;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * shurjoPay, spoken to directly over its REST API.
 *
 * The vendor plugin sends a `Location` header and calls `exit` inside makePayment(), which
 * cannot be used from a controller and cannot be tested. The endpoints it targets are
 * documented here and driven with Laravel's HTTP client instead, so the flow returns a real
 * RedirectResponse and Http::fake() covers it in tests. See docs/DECISIONS.md.
 *
 * Every credential comes from ShurjopaySettings, which the administrator fills in. Nothing
 * is read from the environment, so sandbox and live are one dropdown apart.
 */
final class ShurjopayGateway implements PaymentGateway
{
    private const TOKEN_TTL_SECONDS = 720; // shurjoPay tokens last 15 minutes; refresh at 12.

    private const TIMEOUT_SECONDS = 15;

    /** shurjoPay says a transaction succeeded with this code, and only this code. */
    public const SUCCESS_CODE = '1000';

    public function __construct(private readonly ShurjopaySettings $settings) {}

    public function name(): string
    {
        return 'shurjopay';
    }

    public function initiate(Payment $payment, CustomerDetails $customer): InitiationResult
    {
        $token = $this->token();

        if ($token === null) {
            return InitiationResult::failed('Could not authenticate with the payment gateway.');
        }

        $body = [
            'token' => $token['token'],
            'store_id' => $token['store_id'],
            'prefix' => $this->settings->prefix,
            'currency' => $payment->currency,
            'return_url' => route('payments.shurjopay.return'),
            'cancel_url' => route('payments.shurjopay.cancel'),
            // shurjoPay takes a decimal amount; our ledger is paisa.
            'amount' => (float) Money::toDecimal($payment->amount),
            'order_id' => $payment->merchant_order_id,
            'discsount_amount' => 0,
            'disc_percent' => 0,
            'client_ip' => $customer->ip,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'customer_address' => $customer->address,
            'customer_city' => $customer->city,
            'customer_state' => $customer->city,
            'customer_postcode' => $customer->postcode,
            'customer_country' => $customer->country,
            // Echoed back on verification, so we can tie the response to our own row even
            // if something upstream loses the order id.
            'value1' => $payment->merchant_order_id,
            'value2' => $payment->payableKind(),
            'value3' => (string) $payment->getKey(),
            'value4' => config('app.url'),
        ];

        $response = $this->client()
            ->withToken($token['token'])
            ->post($this->url('/api/secret-pay'), $body);

        $json = (array) $response->json();

        $this->log('initiate', [
            'payment_id' => $payment->getKey(),
            'merchant_order_id' => $payment->merchant_order_id,
            'http_status' => $response->status(),
            'sp_order_id' => $json['sp_order_id'] ?? null,
        ]);

        $checkoutUrl = $json['checkout_url'] ?? null;

        if (! $response->successful() || ! is_string($checkoutUrl) || $checkoutUrl === '') {
            return InitiationResult::failed(
                (string) ($json['message'] ?? 'The payment gateway did not return a checkout link.'),
                $this->scrub($json),
            );
        }

        return new InitiationResult(
            ok: true,
            checkoutUrl: $checkoutUrl,
            gatewayOrderId: (string) ($json['sp_order_id'] ?? ''),
            message: $json['message'] ?? null,
            raw: $this->scrub($json),
        );
    }

    public function verify(string $gatewayOrderId): VerificationResult
    {
        $token = $this->token();

        if ($token === null) {
            return new VerificationResult(
                PaymentStatus::Pending, 0, $this->settings->currency,
                message: 'Could not authenticate with the payment gateway.',
            );
        }

        $response = $this->client()
            ->withToken($token['token'])
            ->post($this->url('/api/verification'), ['order_id' => $gatewayOrderId]);

        // The verification endpoint answers with a single-element array.
        $json = $response->json();
        $row = is_array($json) ? (array) ($json[0] ?? $json) : [];

        $this->log('verify', [
            'sp_order_id' => $gatewayOrderId,
            'http_status' => $response->status(),
            'sp_code' => $row['sp_code'] ?? null,
        ]);

        $code = isset($row['sp_code']) ? (string) $row['sp_code'] : null;

        return new VerificationResult(
            status: $this->mapStatus($code),
            amount: Money::fromDecimal($row['received_amount'] ?? $row['amount'] ?? 0),
            currency: (string) ($row['currency'] ?? $this->settings->currency),
            code: $code,
            message: isset($row['sp_message']) ? (string) $row['sp_message'] : null,
            method: isset($row['method']) ? (string) $row['method'] : null,
            bankTrxId: isset($row['bank_trx_id']) ? (string) $row['bank_trx_id'] : null,
            invoiceNo: isset($row['invoice_no']) ? (string) $row['invoice_no'] : null,
            merchantOrderId: isset($row['customer_order_id'])
                ? (string) $row['customer_order_id']
                : (isset($row['value1']) ? (string) $row['value1'] : null),
            raw: $this->scrub($row),
        );
    }

    /** Ask shurjoPay whether it can reach us with these credentials. */
    public function testConnection(): array
    {
        Cache::forget($this->tokenCacheKey());

        $token = $this->token();

        return $token === null
            ? ['ok' => false, 'message' => 'Authentication failed. Check the username, password and API URL.']
            : ['ok' => true, 'message' => 'Connected. Store ID '.$token['store_id'].'.'];
    }

    /**
     * A cached bearer token, refreshed automatically before shurjoPay expires it.
     *
     * @return array{token: string, store_id: string}|null
     */
    private function token(bool $forceRefresh = false): ?array
    {
        $key = $this->tokenCacheKey();

        if ($forceRefresh) {
            Cache::forget($key);
        }

        $cached = Cache::get($key);

        if (is_array($cached) && isset($cached['token'], $cached['store_id'])) {
            return $cached;
        }

        if ($this->settings->username === '' || $this->settings->password === '') {
            $this->log('auth.missing_credentials', []);

            return null;
        }

        $response = $this->client()->post($this->url('/api/get_token'), [
            'username' => $this->settings->username,
            'password' => $this->settings->password,
        ]);

        $json = (array) $response->json();

        if (! $response->successful() || empty($json['token']) || empty($json['store_id'])) {
            $this->log('auth.failed', ['http_status' => $response->status(), 'message' => $json['message'] ?? null]);

            return null;
        }

        $token = ['token' => (string) $json['token'], 'store_id' => (string) $json['store_id']];

        Cache::put($key, $token, self::TOKEN_TTL_SECONDS);

        return $token;
    }

    /** The cache key is per environment: a sandbox token is worthless on live. */
    private function tokenCacheKey(): string
    {
        return 'shurjopay:token:'.md5($this->settings->api_url.'|'.$this->settings->username);
    }

    private function client(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS)
            // Retry only connection-level failures. A 4xx from the gateway is an answer,
            // not a blip, and hammering it would not change the reply.
            ->retry(2, 500, fn (\Throwable $e): bool => $e instanceof ConnectionException, false)
            ->withOptions(['verify' => $this->settings->ssl_verify]);
    }

    private function url(string $path): string
    {
        return rtrim($this->settings->api_url, '/').$path;
    }

    /** Map shurjoPay's response code onto our own status vocabulary. */
    private function mapStatus(?string $code): PaymentStatus
    {
        return match ($code) {
            self::SUCCESS_CODE => PaymentStatus::Paid,
            // Declined, cancelled by the customer, or refused by the issuing bank.
            '1001', '1002', '1005', '1006', '1007' => PaymentStatus::Failed,
            '1003', '1004' => PaymentStatus::Cancelled,
            // Anything else (including no code at all) is still in flight; the reconcile
            // job asks again rather than guessing.
            default => PaymentStatus::Pending,
        };
    }

    /**
     * Strip anything secret before the payload is written to the database or the log.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function scrub(array $payload): array
    {
        foreach (['token', 'password', 'store_id'] as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }

    /** @param array<string, mixed> $context */
    private function log(string $event, array $context): void
    {
        Log::channel('shurjopay')->info($event, $context + [
            'mode' => $this->settings->mode,
            'api_url' => $this->settings->api_url,
        ]);
    }
}
