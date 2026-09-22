<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\PaymentStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Jobs\VerifyShurjopayPayment;
use App\Models\CustomInvoice;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\CustomerDetails;
use App\Services\ShurjopayService;
use App\Settings\ShurjopaySettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * shurjoPay's three touchpoints.
 *
 * None of them believes its own query string. `order_id` names a payment; what happened to
 * that payment is decided by asking shurjoPay's verification endpoint.
 */
final class ShurjopayController extends Controller
{
    public function __construct(
        private readonly ShurjopayService $shurjopay,
        private readonly ShurjopaySettings $settings,
    ) {}

    /** Begin a payment for an order, a deposit or an invoice. */
    public function start(Request $request, string $type, int $id): RedirectResponse
    {
        $payable = $this->resolvePayable($type, $id);
        $user = $request->user();

        abort_if($payable === null, 404);
        abort_unless($this->ownsPayable($payable, $user?->getKey()), 403);

        $amount = $this->amountFor($payable);

        $customer = new CustomerDetails(
            name: $user?->name ?? 'Guest',
            email: $user?->email ?? $this->emailFor($payable),
            phone: $user?->phone ?? $this->phoneFor($payable) ?? '01700000000',
            address: $this->settings->customer_city,
            city: $this->settings->customer_city,
            postcode: $this->settings->customer_postcode,
            country: $this->settings->customer_country,
            ip: (string) $request->ip(),
        );

        try {
            $result = $this->shurjopay->start($payable, $user, $customer, $amount);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($result['checkout_url']);
    }

    /** The customer came back from the gateway. */
    public function return(Request $request): RedirectResponse
    {
        $payment = $this->verify($request, 'return');

        if ($payment === null) {
            return redirect()->route('home')->with('error', 'We could not find that payment.');
        }

        return $this->redirectForPayment($payment, $payment->isPaid()
            ? 'Payment received. Thank you!'
            : 'We have not received a confirmation for that payment yet.');
    }

    /** They pressed cancel — but verify anyway, because they may have paid first. */
    public function cancel(Request $request): RedirectResponse
    {
        $payment = $this->verify($request, 'cancel');

        if ($payment === null) {
            return redirect()->route('home')->with('error', 'That payment was cancelled.');
        }

        return $this->redirectForPayment($payment, $payment->isPaid()
            ? 'Payment received. Thank you!'
            : 'Payment cancelled. You can try again whenever you are ready.');
    }

    /**
     * Server-to-server notification.
     *
     * Answers immediately and does the verification on the queue: shurjoPay retries an
     * endpoint that is slow to respond, and a retry storm helps nobody.
     */
    public function ipn(Request $request): JsonResponse
    {
        $spOrderId = (string) ($request->input('order_id') ?? $request->query('order_id', ''));

        Log::channel('shurjopay')->info('ipn.received', [
            'sp_order_id' => $spOrderId,
            'ip' => $request->ip(),
        ]);

        if ($spOrderId !== '') {
            VerifyShurjopayPayment::dispatch($spOrderId, 'ipn');
        }

        return response()->json(['status' => 'ok']);
    }

    private function verify(Request $request, string $source): ?Payment
    {
        $spOrderId = (string) $request->query('order_id', '');

        if ($spOrderId === '') {
            return null;
        }

        return $this->shurjopay->verifyAndApply($spOrderId, $source);
    }

    private function redirectForPayment(Payment $payment, string $message): RedirectResponse
    {
        $key = $payment->status === PaymentStatus::Paid ? 'status' : 'error';
        $payable = $payment->payable;

        $target = match (true) {
            $payable instanceof Order => route('order.pay', $payable),
            $payable instanceof Deposit => url('/customer/deposits'),
            $payable instanceof CustomInvoice => url('/customer/invoices'),
            default => route('home'),
        };

        return redirect()->to($target)->with($key, $message);
    }

    private function resolvePayable(string $type, int $id): ?Model
    {
        return match ($type) {
            'order' => Order::find($id),
            'deposit' => Deposit::find($id),
            'invoice' => CustomInvoice::find($id),
            default => null,
        };
    }

    private function ownsPayable(Model $payable, ?int $userId): bool
    {
        // A guest order is reachable only through the signed link that granted session
        // access on the pay page.
        if ($payable instanceof Order && $payable->user_id === null) {
            return in_array($payable->getKey(), (array) session('order_access', []), true);
        }

        return $userId !== null && $payable->getAttribute('user_id') === $userId;
    }

    private function amountFor(Model $payable): int
    {
        return match (true) {
            $payable instanceof Order => $payable->total - $payable->refunded_amount,
            $payable instanceof Deposit => $payable->payable,
            $payable instanceof CustomInvoice => $payable->amount,
            default => 0,
        };
    }

    private function emailFor(Model $payable): string
    {
        return match (true) {
            $payable instanceof Order => $payable->customerEmail(),
            default => (string) ($payable->getAttribute('user')?->email ?? 'guest@example.com'),
        };
    }

    private function phoneFor(Model $payable): ?string
    {
        return $payable instanceof Order ? $payable->customer_phone : null;
    }
}
