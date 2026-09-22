<?php

declare(strict_types=1);

use App\Enums\CouponType;
use App\Enums\FieldType;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\DomainException;
use App\Exceptions\InsufficientBalance;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake();

    $this->checkout = app(CheckoutService::class);
    $this->wallet = PaymentMethod::factory()->wallet()->create();
    $this->manual = PaymentMethod::factory()->create();
    $this->product = Product::factory()->priced(50_000)->withStock(10)->create();
});

it('places a wallet order, debits the balance and reserves stock', function (): void {
    $user = User::factory()->withBalance(200_000)->create();

    $order = $this->checkout->placeFromBuyNow(
        product: $this->product,
        quantity: 2,
        fields: [],
        deliveryEmail: 'buyer@example.com',
        method: $this->wallet,
        couponCode: null,
        user: $user,
    );

    expect($order->total)->toBe(100_000)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($user->fresh()->balance)->toBe(100_000)
        ->and($this->product->fresh()->stock)->toBe(8)
        ->and($this->product->fresh()->total_sold)->toBe(2);
});

it('leaves a manual order unpaid and pending payment', function (): void {
    $order = $this->checkout->placeFromBuyNow(
        product: $this->product,
        quantity: 1,
        fields: [],
        deliveryEmail: 'guest@example.com',
        method: $this->manual,
        couponCode: null,
        user: null,
        guestEmail: 'guest@example.com',
    );

    expect($order->payment_status)->toBe(OrderPaymentStatus::Unpaid)
        ->and($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->user_id)->toBeNull()
        ->and($order->guest_email)->toBe('guest@example.com')
        // Stock is reserved even before payment, so two people cannot buy the last one.
        ->and($this->product->fresh()->stock)->toBe(9);
});

it('refuses a wallet order the balance cannot cover', function (): void {
    $user = User::factory()->withBalance(1_000)->create();

    expect(fn () => $this->checkout->placeFromBuyNow(
        product: $this->product,
        quantity: 1,
        fields: [],
        deliveryEmail: 'poor@example.com',
        method: $this->wallet,
        couponCode: null,
        user: $user,
    ))->toThrow(InsufficientBalance::class);

    // Nothing may survive the rollback.
    expect($this->product->fresh()->stock)->toBe(10)
        ->and(Order::count())->toBe(0);
});

it('refuses to sell more than the stock on hand', function (): void {
    $product = Product::factory()->priced(1_000)->withStock(3)->create();

    expect(fn () => $this->checkout->placeFromBuyNow(
        product: $product,
        quantity: 5,
        fields: [],
        deliveryEmail: 'a@example.com',
        method: $this->manual,
        couponCode: null,
        user: null,
        guestEmail: 'a@example.com',
    ))->toThrow(DomainException::class, 'only has 3 left in stock');
});

it('applies a percentage coupon to the total', function (): void {
    Coupon::create([
        'code' => 'SAVE10',
        'label' => '10% off applied!',
        'type' => CouponType::Percent,
        'value' => 10,
        'min_order' => 0,
        'is_active' => true,
    ]);

    $order = $this->checkout->placeFromBuyNow(
        product: $this->product,
        quantity: 1,
        fields: [],
        deliveryEmail: 'a@example.com',
        method: $this->manual,
        couponCode: 'SAVE10',
        user: null,
        guestEmail: 'a@example.com',
    );

    expect($order->subtotal)->toBe(50_000)
        ->and($order->discount)->toBe(5_000)
        ->and($order->total)->toBe(45_000);
});

it('refuses a coupon below its minimum order', function (): void {
    Coupon::create([
        'code' => 'BIG',
        'type' => CouponType::Fixed,
        'value' => 10_000,
        'min_order' => 500_000,
        'is_active' => true,
    ]);

    expect(fn () => $this->checkout->placeFromBuyNow(
        product: $this->product,
        quantity: 1,
        fields: [],
        deliveryEmail: 'a@example.com',
        method: $this->manual,
        couponCode: 'BIG',
        user: null,
        guestEmail: 'a@example.com',
    ))->toThrow(DomainException::class, 'Minimum order');
});

it('adds the payment method fee to the total', function (): void {
    $method = PaymentMethod::factory()->withFee(250, 500)->create();

    $order = $this->checkout->placeFromBuyNow(
        product: $this->product,
        quantity: 1,
        fields: [],
        deliveryEmail: 'a@example.com',
        method: $method,
        couponCode: null,
        user: null,
        guestEmail: 'a@example.com',
    );

    // 2.5% of 50,000 paisa = 1,250, plus a 500 flat fee.
    expect($order->fee)->toBe(1_750)
        ->and($order->total)->toBe(51_750);
});

it('requires a value for every required product field', function (): void {
    $product = Product::factory()->priced(1_000)->withStock(5)->create();
    $product->fields()->create([
        'key' => 'player_id',
        'label' => 'Player / UID',
        'type' => FieldType::Text,
        'required' => true,
        'sort_order' => 0,
    ]);

    expect(fn () => $this->checkout->placeFromBuyNow(
        product: $product->fresh(),
        quantity: 1,
        fields: ['player_id' => '   '],
        deliveryEmail: 'a@example.com',
        method: $this->manual,
        couponCode: null,
        user: null,
        guestEmail: 'a@example.com',
    ))->toThrow(DomainException::class, 'Player / UID is required.');
});

it('freezes the product details onto the order item', function (): void {
    $order = $this->checkout->placeFromBuyNow(
        product: $this->product,
        quantity: 1,
        fields: [],
        deliveryEmail: 'a@example.com',
        method: $this->manual,
        couponCode: null,
        user: null,
        guestEmail: 'a@example.com',
    );

    $originalName = $this->product->name;
    $this->product->update(['name' => 'Renamed', 'price' => 999_999]);

    $item = $order->items()->first();

    // History must not move when the catalogue does.
    expect($item->product_snapshot['name'])->toBe($originalName)
        ->and($item->unit_price)->toBe(50_000);
});
