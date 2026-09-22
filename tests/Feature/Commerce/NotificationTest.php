<?php

declare(strict_types=1);

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(EmailTemplateSeeder::class);

    Mail::fake();
    Notification::fake();

    $this->method = PaymentMethod::factory()->create();
    $this->product = Product::factory()->priced(30_000)->withStock(5)->create();
});

it('emails a signed-in customer when their order is placed', function (): void {
    $user = User::factory()->create(['email' => 'buyer@example.com']);

    app(CheckoutService::class)->placeFromBuyNow(
        product: $this->product, quantity: 1, fields: [], deliveryEmail: $user->email,
        method: $this->method, couponCode: null, user: $user,
    );

    Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail): bool => $mail->hasTo('buyer@example.com'));
});

it('emails a guest at the address they gave', function (): void {
    app(CheckoutService::class)->placeFromBuyNow(
        product: $this->product, quantity: 1, fields: [], deliveryEmail: 'guest@example.com',
        method: $this->method, couponCode: null, user: null, guestEmail: 'guest@example.com',
    );

    // Guests have no account to notify, so the email is the only way back to the order.
    Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail): bool => $mail->hasTo('guest@example.com'));
});

it('renders a templated email without throwing', function (): void {
    $mail = new TemplatedMail(
        subjectLine: 'Your order is complete',
        bodyMarkdown: "Hi there,\n\nYour order **VD-1** is ready.",
        actionUrl: 'https://example.test/order',
        actionText: 'View your order',
    );

    $rendered = $mail->render();

    expect($rendered)->toContain('Your order')
        ->and($rendered)->toContain('View your order');
});

it('substitutes template variables', function (): void {
    $template = EmailTemplate::where('key', 'order_completed')->firstOrFail();

    $body = $template->render('body', [
        'name' => 'Sadia',
        'order_number' => 'VD-2601-000042',
        'delivery_note' => 'Invitation sent.',
    ]);

    expect($body)->toContain('Sadia')
        ->toContain('VD-2601-000042')
        ->toContain('Invitation sent.')
        ->not->toContain('{{');
});
