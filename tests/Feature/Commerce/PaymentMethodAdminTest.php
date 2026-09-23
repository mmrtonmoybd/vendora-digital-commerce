<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ContentTranslator;

/**
 * What an administrator changes about a payment method has to reach the checkout.
 *
 * Both halves matter: turning one off must remove it from the list *and* refuse it if
 * somebody posts its id anyway, and renaming it must change the label customers read.
 */
it('offers only the methods that are switched on', function (): void {
    $on = PaymentMethod::factory()->create(['name' => 'bKash', 'is_active' => true, 'available_for_orders' => true]);
    $off = PaymentMethod::factory()->create(['name' => 'Rocket', 'is_active' => false, 'available_for_orders' => true]);
    $ordersOff = PaymentMethod::factory()->create(['name' => 'Deposits only', 'is_active' => true, 'available_for_orders' => false]);

    $offered = PaymentMethod::forOrders()->pluck('id');

    expect($offered)->toContain($on->id)
        ->not->toContain($off->id)
        ->not->toContain($ordersOff->id);
});

it('refuses a switched-off method even if its id is posted', function (): void {
    $method = PaymentMethod::factory()->create(['is_active' => false]);
    $user = User::factory()->create();
    $product = Product::factory()->priced(10_000)->withStock(5)->create();

    // The list is a convenience; the service is the rule.
    expect(fn () => app(CheckoutService::class)->placeFromBuyNow(
        product: $product,
        quantity: 1,
        fields: [],
        deliveryEmail: $user->email,
        method: $method,
        couponCode: null,
        user: $user,
    ))->toThrow(DomainException::class);
});

it('shows the label an administrator typed', function (): void {
    $method = PaymentMethod::factory()->create(['name' => 'bKash Personal', 'is_active' => true, 'available_for_orders' => true]);

    expect($method->t('name'))->toBe('bKash Personal');

    $method->update(['name' => 'bKash Merchant']);

    expect($method->fresh()->t('name'))->toBe('bKash Merchant');
});

it('shows a translated label to a Bengali customer', function (): void {
    $method = PaymentMethod::factory()->create(['name' => 'Card / Online']);

    app(ContentTranslator::class)->put(PaymentMethod::class, $method->getKey(), 'name', 'bn', 'কার্ড / অনলাইন');

    app()->setLocale('bn');
    expect($method->t('name'))->toBe('কার্ড / অনলাইন');

    app()->setLocale('en');
    expect($method->t('name'))->toBe('Card / Online');
});
