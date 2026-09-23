<?php

declare(strict_types=1);

use App\Enums\WalletTxType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\WalletService;
use Filament\Facades\Filament;

/**
 * The customer's own screens, rendered with their own data in them.
 *
 * An empty table renders happily however broken its columns are. Two column formatters in
 * this panel referred to a variable that no longer existed — they would have fatalled on
 * the first row with money in it, and nothing noticed, because no test had ever put a row
 * there. These do.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel('customer');

    $this->user = User::factory()->create();
    $this->method = PaymentMethod::factory()->create();
    $this->product = Product::factory()->priced(45_000)->withStock(10)->create();

    $order = app(CheckoutService::class)->placeFromBuyNow(
        product: $this->product,
        quantity: 2,
        fields: [],
        deliveryEmail: $this->user->email,
        method: $this->method,
        couponCode: null,
        user: $this->user,
    );

    app(OrderService::class)->markPaid($order, 'gateway');

    // Both a credit and a debit, so the signed formatting is exercised in both directions.
    app(WalletService::class)->credit($this->user, 250_00, WalletTxType::Deposit, idempotencyKey: 'test-credit');
    app(WalletService::class)->debit($this->user, 100_00, WalletTxType::OrderPayment, idempotencyKey: 'test-debit');

    $this->actingAs($this->user);
});

it('renders every customer resource index with the customer\'s own rows on it', function (): void {
    $failures = [];

    foreach (Filament::getPanel('customer')->getResources() as $resource) {
        if (! $resource::hasPage('index')) {
            continue;
        }

        $response = $this->get($resource::getUrl('index'));

        if ($response->getStatusCode() !== 200) {
            $failures[] = class_basename($resource).' → '.$response->getStatusCode();
        }
    }

    expect($failures)->toBe([]);
});

it('opens a record in every customer resource that has one', function (): void {
    $failures = [];

    foreach (Filament::getPanel('customer')->getResources() as $resource) {
        $record = $resource::getEloquentQuery()->first();

        if ($record === null) {
            continue;
        }

        foreach (['view', 'edit'] as $page) {
            if (! $resource::hasPage($page)) {
                continue;
            }

            $response = $this->get($resource::getUrl($page, ['record' => $record]));

            if ($response->getStatusCode() !== 200) {
                $failures[] = class_basename($resource).' '.$page.' → '.$response->getStatusCode();
            }
        }
    }

    expect($failures)->toBe([]);
});

it('renders every customer page', function (): void {
    $failures = [];

    foreach (Filament::getPanel('customer')->getPages() as $page) {
        $response = $this->get($page::getUrl());

        if ($response->getStatusCode() !== 200) {
            $failures[] = class_basename($page).' → '.$response->getStatusCode();
        }
    }

    expect($failures)->toBe([]);
});
