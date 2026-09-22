<?php

declare(strict_types=1);

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\DomainException;
use App\Exceptions\InvalidTransition;
use App\Models\Admin;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake();

    $this->orders = app(OrderService::class);
    $this->checkout = app(CheckoutService::class);
    $this->admin = Admin::factory()->create();
    $this->manual = PaymentMethod::factory()->create();
    $this->product = Product::factory()->priced(40_000)->withStock(10)->create();
    $this->user = User::factory()->withBalance(0)->create();
});

function placeOrder($test, ?PaymentMethod $method = null)
{
    return $test->checkout->placeFromBuyNow(
        product: $test->product,
        quantity: 1,
        fields: [],
        deliveryEmail: $test->user->email,
        method: $method ?? $test->manual,
        couponCode: null,
        user: $test->user,
    );
}

it('marks an order paid only once however many times it is told', function (): void {
    $order = placeOrder($this);

    $this->orders->markPaid($order, 'gateway');
    $this->orders->markPaid($order->fresh(), 'ipn');
    $this->orders->markPaid($order->fresh(), 'reconcile');

    $order->refresh();

    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Pending)
        // One history row for the payment, not three.
        ->and($order->histories()->where('note', 'like', '%Payment confirmed%')->count())->toBe(1);
});

it('lets an admin approve a payment the gateway could not confirm', function (): void {
    $order = placeOrder($this);

    $this->orders->markPaidByAdmin($order, $this->admin, 'Gateway timed out; bank statement shows the payment.');

    $order->refresh();

    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->histories()->reorder()->orderByDesc('id')->first()->note)
        ->toContain('Manually approved')
        ->toContain('bank statement');
});

it('refuses a manual approval without a reason', function (): void {
    $order = placeOrder($this);

    expect(fn () => $this->orders->markPaidByAdmin($order, $this->admin, '   '))
        ->toThrow(DomainException::class, 'A reason is required');
});

it('refuses to approve an order that is already paid', function (): void {
    $order = placeOrder($this);
    $this->orders->markPaid($order, 'manual');

    expect(fn () => $this->orders->markPaidByAdmin($order->fresh(), $this->admin, 'again'))
        ->toThrow(DomainException::class, 'already paid');
});

it('refunds to the wallet and puts the stock back', function (): void {
    $order = placeOrder($this);
    $this->orders->markPaid($order, 'manual');

    expect($this->product->fresh()->stock)->toBe(9);

    $this->orders->refund($order->fresh(), null, 'Customer changed their mind', $this->admin);

    $order->refresh();

    expect($order->payment_status)->toBe(OrderPaymentStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded)
        ->and($order->refunded_amount)->toBe(40_000)
        ->and($this->user->fresh()->balance)->toBe(40_000)
        ->and($this->product->fresh()->stock)->toBe(10);
});

it('supports a partial refund without closing the order', function (): void {
    $order = placeOrder($this);
    $this->orders->markPaid($order, 'manual');

    $this->orders->refund($order->fresh(), 15_000, 'Partial goodwill', $this->admin);

    $order->refresh();

    expect($order->payment_status)->toBe(OrderPaymentStatus::PartiallyRefunded)
        ->and($order->refunded_amount)->toBe(15_000)
        ->and($order->refundableAmount())->toBe(25_000)
        ->and($this->user->fresh()->balance)->toBe(15_000)
        // A partial refund is not a cancellation, so the stock stays sold.
        ->and($this->product->fresh()->stock)->toBe(9);
});

it('never refunds more than is left on the order', function (): void {
    $order = placeOrder($this);
    $this->orders->markPaid($order, 'manual');

    $this->orders->refund($order->fresh(), 999_999, 'Too much', $this->admin);

    expect($order->fresh()->refunded_amount)->toBe(40_000)
        ->and($this->user->fresh()->balance)->toBe(40_000);
});

it('refuses to refund an unpaid order', function (): void {
    $order = placeOrder($this);

    expect(fn () => $this->orders->refund($order, 1_000, 'no', $this->admin))
        ->toThrow(DomainException::class, 'Only a paid order can be refunded');
});

it('releases stock when an unpaid order is cancelled', function (): void {
    $order = placeOrder($this);

    expect($this->product->fresh()->stock)->toBe(9);

    $this->orders->cancel($order, 'Not paid in time');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->product->fresh()->stock)->toBe(10);
});

it('refunds the wallet when a paid order is cancelled', function (): void {
    $order = placeOrder($this);
    $this->orders->markPaid($order, 'manual');

    $this->orders->cancel($order->fresh(), 'Out of stock after all', $this->admin);

    expect($this->user->fresh()->balance)->toBe(40_000)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->product->fresh()->stock)->toBe(10);
});

it('enforces the allowed transitions', function (): void {
    $order = placeOrder($this);
    $this->orders->cancel($order, 'done');

    // Cancelled is terminal.
    expect(fn () => $this->orders->transition($order->fresh(), OrderStatus::Completed, 'nope'))
        ->toThrow(InvalidTransition::class);
});

it('refuses to complete an unpaid order', function (): void {
    $order = placeOrder($this);

    expect(fn () => $this->orders->complete($order, 'here you go', $this->admin))
        ->toThrow(DomainException::class, 'unpaid order cannot be completed');
});

it('rejects a duplicate transaction id across orders', function (): void {
    $first = placeOrder($this);
    $second = placeOrder($this);

    $this->orders->submitManualProof($first, '01700000000', 'TRX123456');

    expect(fn () => $this->orders->submitManualProof($second, '01700000000', 'TRX123456'))
        ->toThrow(DomainException::class, 'already been submitted');
});
