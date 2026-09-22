<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Events\PaymentFailed;
use App\Events\PaymentVerified;
use App\Exceptions\DomainException;
use App\Models\CustomInvoice;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Contracts\CustomerDetails;
use App\Payments\Gateways\ShurjopayGateway;
use App\Settings\ShurjopaySettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates a gateway payment from first redirect to final settlement.
 *
 * `verifyAndApply` is the only place in the system where a gateway payment becomes paid.
 * The return URL, the IPN and the reconcile job all funnel through it, it locks the payment
 * row, and it returns early if the work is already done — so the three of them racing each
 * other settles the payable exactly once.
 */
final class ShurjopayService
{
    public function __construct(
        private readonly ShurjopayGateway $gateway,
        private readonly ShurjopaySettings $settings,
        private readonly OrderService $orders,
        private readonly DepositService $deposits,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * Create a payment attempt and hand back where the customer should go.
     *
     * @return array{payment: Payment, checkout_url: string}
     */
    public function start(Model $payable, ?User $user, CustomerDetails $customer, int $amount): array
    {
        if (! $this->settings->enabled) {
            throw new DomainException('Online card payment is currently unavailable.');
        }

        if (mb_strlen($this->settings->prefix) > 5) {
            throw new DomainException('The shurjoPay prefix must be at most 5 characters.');
        }

        $payment = Payment::create([
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'user_id' => $user?->getKey(),
            'gateway' => 'shurjopay',
            'merchant_order_id' => $this->merchantOrderId($payable),
            'amount' => $amount,
            'currency' => $this->settings->currency,
            'status' => PaymentStatus::Initiated,
            'ip' => $customer->ip,
        ]);

        $result = $this->gateway->initiate($payment, $customer);

        $payment->forceFill([
            'request_payload' => ['customer' => $customer->email, 'amount' => $amount],
            'attempts' => $payment->attempts + 1,
        ])->save();

        if (! $result->ok || $result->checkoutUrl === null) {
            $payment->forceFill([
                'status' => PaymentStatus::Failed,
                'sp_message' => $result->message,
                'verify_response' => $result->raw,
            ])->save();

            $this->event($payment, 'initiate', $result->raw);

            throw new DomainException($result->message ?: 'The payment gateway could not start this payment.');
        }

        $payment->forceFill([
            'status' => PaymentStatus::Pending,
            'sp_order_id' => $result->gatewayOrderId ?: null,
            'checkout_url' => $result->checkoutUrl,
        ])->save();

        $this->event($payment, 'initiate', $result->raw);

        return ['payment' => $payment, 'checkout_url' => $result->checkoutUrl];
    }

    /**
     * Ask shurjoPay what happened, then act on the answer.
     *
     * @param  string  $source  return | cancel | ipn | reconcile | admin
     */
    public function verifyAndApply(string $spOrderId, string $source): ?Payment
    {
        $payment = Payment::where('sp_order_id', $spOrderId)->first();

        if ($payment === null) {
            Log::channel('shurjopay')->warning('verify.unknown_order', ['sp_order_id' => $spOrderId, 'source' => $source]);

            return null;
        }

        $result = $this->gateway->verify($spOrderId);

        $this->event($payment, $source, $result->raw);

        return DB::transaction(function () use ($payment, $result, $source): Payment {
            /** @var Payment $locked */
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $locked->forceFill(['last_checked_at' => now()])->save();

            // Already settled. A second callback must not credit anything again.
            if ($locked->status === PaymentStatus::Paid) {
                return $locked;
            }

            $locked->forceFill([
                'sp_code' => $result->code,
                'sp_message' => $result->message,
                'method' => $result->method,
                'bank_trx_id' => $result->bankTrxId,
                'invoice_no' => $result->invoiceNo,
                'verify_response' => $result->raw,
            ])->save();

            if ($result->status !== PaymentStatus::Paid) {
                if ($result->status !== PaymentStatus::Pending) {
                    $locked->forceFill(['status' => $result->status])->save();
                    PaymentFailed::dispatch($locked);
                }

                return $locked;
            }

            // A "paid" that does not match what we asked for is not a payment we accept.
            if (! $this->amountMatches($locked, $result->amount) || ! $this->currencyMatches($locked, $result->currency)) {
                $locked->forceFill([
                    'status' => PaymentStatus::Failed,
                    'sp_message' => sprintf(
                        'Amount or currency mismatch. Expected %d %s, gateway reported %d %s.',
                        $locked->amount, $locked->currency, $result->amount, $result->currency,
                    ),
                ])->save();

                Log::channel('security')->critical('shurjopay.amount_mismatch', [
                    'payment_id' => $locked->getKey(),
                    'expected' => $locked->amount,
                    'received' => $result->amount,
                    'sp_order_id' => $locked->sp_order_id,
                ]);

                PaymentFailed::dispatch($locked);

                return $locked;
            }

            if ($result->merchantOrderId !== null && $result->merchantOrderId !== $locked->merchant_order_id) {
                Log::channel('security')->critical('shurjopay.order_id_mismatch', [
                    'payment_id' => $locked->getKey(),
                    'expected' => $locked->merchant_order_id,
                    'received' => $result->merchantOrderId,
                ]);

                $locked->forceFill(['status' => PaymentStatus::Failed, 'sp_message' => 'Merchant order id mismatch.'])->save();
                PaymentFailed::dispatch($locked);

                return $locked;
            }

            $locked->forceFill([
                'status' => PaymentStatus::Paid,
                'verified_at' => now(),
            ])->save();

            $this->applyToPayable($locked, $source);

            PaymentVerified::dispatch($locked);

            return $locked;
        }, 3);
    }

    /** Settle whatever this payment was for. */
    private function applyToPayable(Payment $payment, string $source): void
    {
        $payable = $payment->payable;

        match (true) {
            $payable instanceof Order => (function () use ($payable, $source): void {
                $this->orders->markPaid($payable, 'gateway:'.$source);
                OrderPaid::dispatch($payable->refresh());
            })(),
            $payable instanceof Deposit => (function () use ($payable, $payment): void {
                $payable->forceFill(['payment_id' => $payment->getKey()])->save();
                $this->deposits->markGatewayPaid($payable);
            })(),
            $payable instanceof CustomInvoice => (function () use ($payable, $payment): void {
                $payable->forceFill(['payment_id' => $payment->getKey()])->save();
                $this->invoices->markPaid($payable, 'gateway');
            })(),
            default => Log::channel('shurjopay')->error('verify.unknown_payable', [
                'payment_id' => $payment->getKey(),
                'payable_type' => $payment->payable_type,
            ]),
        };
    }

    private function amountMatches(Payment $payment, int $received): bool
    {
        // Gateways round to two decimals; one paisa of slack avoids false alarms without
        // letting a real shortfall through.
        return abs($payment->amount - $received) <= 1;
    }

    private function currencyMatches(Payment $payment, string $received): bool
    {
        return mb_strtoupper($received) === mb_strtoupper($payment->currency);
    }

    /** Unique per attempt so a retry after a failure is a genuinely new payment. */
    private function merchantOrderId(Model $payable): string
    {
        $kind = match (true) {
            $payable instanceof Order => 'O',
            $payable instanceof Deposit => 'D',
            $payable instanceof CustomInvoice => 'I',
            default => 'X',
        };

        return $this->settings->prefix.$kind.$payable->getKey().Str::upper(Str::random(6));
    }

    /** @param array<string, mixed> $payload */
    private function event(Payment $payment, string $source, array $payload): void
    {
        $payment->events()->create([
            'source' => $source,
            'payload' => $payload,
            'ip' => request()->ip(),
        ]);
    }

    /** The URLs the merchant must register in the shurjoPay panel. */
    public function callbackUrls(): array
    {
        return [
            'return_url' => route('payments.shurjopay.return'),
            'cancel_url' => route('payments.shurjopay.cancel'),
            'ipn_url' => route('payments.shurjopay.ipn'),
        ];
    }
}
