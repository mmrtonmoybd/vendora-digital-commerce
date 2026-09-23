<?php

declare(strict_types=1);

use App\Http\Middleware\SetLiteSpeedVaryCookie;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\PdfService;
use App\Settings\CurrencySettings;
use App\Support\Currencies;
use App\Support\Money;

function enableCurrencies(array $enabled, string $default = 'BDT', int $usdRate = 12000): void
{
    $settings = app(CurrencySettings::class);
    $settings->enabled_currencies = $enabled;
    $settings->default_currency = $default;
    $settings->rates = ['USD' => $usdRate];
    $settings->show_switcher = true;
    $settings->show_settlement_note = true;
    $settings->save();
}

afterEach(function (): void {
    // The current currency is process-static; a leak would misprice the next test.
    Currencies::forgetCurrent();
});

it('shows taka by default', function (): void {
    enableCurrencies(['BDT']);

    expect(Currencies::default())->toBe('BDT')
        ->and(Money::format(299_000))->toBe('৳ 2,990');
});

it('cannot have taka turned off', function (): void {
    enableCurrencies(['USD'], 'USD');

    // The shop settles in taka. Configuring it away would leave nothing to charge in.
    expect(Currencies::enabled())->toContain('BDT');
});

it('converts for display without touching the stored amount', function (): void {
    enableCurrencies(['BDT', 'USD']);
    Currencies::setCurrent('USD');

    expect(Money::format(299_000))->toBe('$ 24.92')
        ->and(Money::formatBase(299_000))->toBe('৳ 2,990');
});

it('follows the rate an administrator sets', function (): void {
    enableCurrencies(['BDT', 'USD'], usdRate: 10_000);
    Currencies::setCurrent('USD');

    // ৳100 to the dollar makes ৳2,990 exactly $29.90.
    expect(Money::format(299_000))->toBe('$ 29.90');
});

it('falls back to taka rather than dividing by a missing rate', function (): void {
    $settings = app(CurrencySettings::class);
    $settings->enabled_currencies = ['BDT', 'USD'];
    $settings->default_currency = 'BDT';
    $settings->rates = [];
    $settings->save();

    Currencies::setCurrent('USD');

    // A missing rate must not divide by zero or hand out free money.
    expect(Money::format(299_000))->toBe('$ 2,990.00');
});

it('remembers a chosen currency for the session', function (): void {
    enableCurrencies(['BDT', 'USD']);

    $this->get(route('currency.switch', 'USD'))->assertRedirect();

    $this->get('/')->assertOk()->assertSee('$', escape: false);
});

it('ignores a currency that is not enabled', function (): void {
    enableCurrencies(['BDT']);
    $user = User::factory()->create(['currency' => null]);

    $this->actingAs($user)->get(route('currency.switch', 'USD'))->assertRedirect();

    expect($user->fresh()->currency)->toBeNull();
});

it('splits the edge cache by currency', function (): void {
    enableCurrencies(['BDT', 'USD']);
    $cookie = SetLiteSpeedVaryCookie::COOKIE;

    $bdt = $this->get('/')->getCookie($cookie, false)?->getValue();

    $this->get(route('currency.switch', 'USD'));
    $usd = $this->get('/')->getCookie($cookie, false)?->getValue();

    expect($bdt)->toContain('BDT')
        ->and($usd)->toContain('USD')
        ->and($bdt)->not->toBe($usd);
});

it('keeps the invoice in taka however the customer was browsing', function (): void {
    enableCurrencies(['BDT', 'USD']);

    $user = User::factory()->create();
    $order = app(CheckoutService::class)->placeFromBuyNow(
        product: Product::factory()->priced(299_000)->withStock(5)->create(),
        quantity: 1,
        fields: [],
        deliveryEmail: $user->email,
        method: PaymentMethod::factory()->create(),
        couponCode: null,
        user: $user,
    );
    app(OrderService::class)->markPaid($order, 'gateway');

    Currencies::setCurrent('USD');

    // An invoice states what was charged. The charge was in taka.
    $html = view('pdf.invoice', [
        'order' => $order->fresh()->load(['items', 'paymentMethod', 'user']),
        'print' => true,
    ])->render();

    expect($html)->toContain('৳')->not->toContain('$ 24.92');

    expect(PdfService::render('pdf.invoice', [
        'order' => $order->fresh()->load(['items', 'paymentMethod', 'user']),
        'print' => false,
    ]))->toStartWith('%PDF');
});

it('charges the stored paisa amount, not a converted one', function (): void {
    enableCurrencies(['BDT', 'USD']);
    Currencies::setCurrent('USD');

    $user = User::factory()->create();
    $order = app(CheckoutService::class)->placeFromBuyNow(
        product: Product::factory()->priced(299_000)->withStock(5)->create(),
        quantity: 2,
        fields: [],
        deliveryEmail: $user->email,
        method: PaymentMethod::factory()->create(),
        couponCode: null,
        user: $user,
    );

    // Browsing in dollars must not change a single stored figure.
    expect($order->subtotal)->toBe(598_000)
        ->and($order->total)->toBe(598_000);
});
