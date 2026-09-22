<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\PdfService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    // Seeded *before* Event::fake(). spatie/laravel-permission invalidates its permission
    // cache from Eloquent saved events, so faking events first leaves the seeder unable to
    // see the permissions it has just written.
    $this->seed(RolesAndPermissionsSeeder::class);

    Event::fake();

    $this->user = User::factory()->withBalance(0)->create();
    $this->method = PaymentMethod::factory()->create();
    $this->product = Product::factory()->priced(40_000)->withStock(10)->create();

    $this->order = app(CheckoutService::class)->placeFromBuyNow(
        product: $this->product,
        quantity: 2,
        fields: [],
        deliveryEmail: $this->user->email,
        method: $this->method,
        couponCode: null,
        user: $this->user,
    );
});

it('ships the fonts the Bengali invoice needs', function (): void {
    // Without these the PDF renders Bengali as mis-ordered glyphs and the ৳ sign vanishes,
    // and nothing else in the suite would notice.
    expect(PdfService::fontsAvailable())->toBeTrue();
});

it('refuses to issue an invoice for an order nobody has paid for', function (): void {
    $this->actingAs($this->user)
        ->get(route('order.invoice', $this->order))
        ->assertNotFound();

    $this->actingAs($this->user)
        ->get(route('order.invoice.print', $this->order))
        ->assertNotFound();
});

it('gives the customer a PDF once the order is paid', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');

    $response = $this->actingAs($this->user)->get(route('order.invoice', $this->order->fresh()));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('keeps the invoice out of every cache in front of it', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');

    $this->actingAs($this->user)
        ->get(route('order.invoice', $this->order->fresh()))
        ->assertHeader('cache-control', 'no-store, private')
        ->assertHeader('x-litespeed-cache-control', 'no-cache');
});

it('shows the printable copy with the totals on it', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');

    $this->actingAs($this->user)
        ->get(route('order.invoice.print', $this->order->fresh()))
        ->assertOk()
        ->assertSee($this->order->order_number)
        ->assertSee($this->product->name)
        ->assertSee(__('invoice.total'));
});

it('does not hand one customer another customer\'s invoice', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('order.invoice', $this->order->fresh()))
        ->assertNotFound();
});

it('renders the invoice in Bengali without falling over', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');

    app()->setLocale('bn');

    $pdf = PdfService::render('pdf.invoice', [
        'order' => $this->order->fresh()->load(['items', 'paymentMethod', 'user']),
        'print' => false,
    ]);

    expect($pdf)->toStartWith('%PDF')
        ->and(strlen($pdf))->toBeGreaterThan(5_000);
});

it('lets staff download an invoice they could never reach as a customer', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');

    $admin = Admin::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.order.invoice', $this->order->fresh()))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('turns away an administrator without the orders permission', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');

    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.order.invoice', $this->order->fresh()))
        ->assertForbidden();
});

it('turns away a signed-out visitor asking for the staff copy', function (): void {
    app(OrderService::class)->markPaid($this->order, 'gateway');

    $this->get(route('admin.order.invoice', $this->order->fresh()))
        ->assertRedirect();
});
