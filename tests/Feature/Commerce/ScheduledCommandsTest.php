<?php

declare(strict_types=1);

use App\Enums\AccessStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WalletTxType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\WalletService;
use App\Settings\CommerceSettings;

it('cancels an unpaid order past the window and releases its stock', function (): void {
    $product = Product::factory()->priced(10_000)->withStock(5)->create();
    $method = PaymentMethod::factory()->create();

    $order = app(CheckoutService::class)->placeFromBuyNow(
        product: $product, quantity: 2, fields: [], deliveryEmail: 'a@example.com',
        method: $method, couponCode: null, user: null, guestEmail: 'a@example.com',
    );

    expect($product->fresh()->stock)->toBe(3);

    $window = app(CommerceSettings::class)->auto_cancel_unpaid_minutes;
    $order->forceFill(['created_at' => now()->subMinutes($window + 10)])->save();

    $this->artisan('orders:cancel-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(5);
});

it('leaves an order alone while it is still inside the window', function (): void {
    $product = Product::factory()->priced(10_000)->withStock(5)->create();
    $method = PaymentMethod::factory()->create();

    $order = app(CheckoutService::class)->placeFromBuyNow(
        product: $product, quantity: 1, fields: [], deliveryEmail: 'a@example.com',
        method: $method, couponCode: null, user: null, guestEmail: 'a@example.com',
    );

    $this->artisan('orders:cancel-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('never cancels an order whose receipt is waiting on staff', function (): void {
    $product = Product::factory()->priced(10_000)->withStock(5)->create();
    $method = PaymentMethod::factory()->create();

    $order = app(CheckoutService::class)->placeFromBuyNow(
        product: $product, quantity: 1, fields: [], deliveryEmail: 'a@example.com',
        method: $method, couponCode: null, user: null, guestEmail: 'a@example.com',
    );

    app(App\Services\OrderService::class)->submitManualProof($order, '01700000000', 'TRX999999');
    $order->forceFill(['created_at' => now()->subDays(2)])->save();

    $this->artisan('orders:cancel-unpaid')->assertSuccessful();

    // The customer has paid; throwing this away would lose their money.
    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('reports a wallet that does not match its ledger', function (): void {
    $user = User::factory()->create();
    app(WalletService::class)->credit($user, 10_000, WalletTxType::Deposit, null, 'x');

    $this->artisan('wallet:reconcile')->assertSuccessful();

    $user->forceFill(['balance' => 50_000])->save();

    // Non-zero exit: drift is a bug, and the command says so.
    $this->artisan('wallet:reconcile')->assertFailed();

    expect($user->fresh()->balance)->toBe(10_000);
});

it('expires gateway payments the gateway never resolved', function (): void {
    $payment = Payment::create([
        'payable_type' => 'order', 'payable_id' => 1,
        'gateway' => 'shurjopay', 'merchant_order_id' => 'OLD1',
        'amount' => 10_000, 'currency' => 'BDT',
        'status' => PaymentStatus::Pending,
        'created_at' => now()->subDays(3),
    ]);

    $this->artisan('shurjopay:reconcile')->assertSuccessful();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Expired);
});

it('auto-confirms access the customer never acknowledged', function (): void {
    $product = Product::factory()->priced(10_000)->withStock(5)->create();
    $method = PaymentMethod::factory()->create();
    $user = User::factory()->create();

    $order = app(CheckoutService::class)->placeFromBuyNow(
        product: $product, quantity: 1, fields: [], deliveryEmail: $user->email,
        method: $method, couponCode: null, user: $user,
    );

    $item = $order->items()->first();
    $item->forceFill([
        'access_status' => AccessStatus::Granted,
        'granted_at' => now()->subDays(10),
    ])->save();

    $this->artisan('access:process')->assertSuccessful();

    expect($item->fresh()->access_status)->toBe(AccessStatus::Confirmed)
        ->and($item->fresh()->confirmed_at)->not->toBeNull();
});
