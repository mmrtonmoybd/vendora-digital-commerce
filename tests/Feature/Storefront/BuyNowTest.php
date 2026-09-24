<?php

declare(strict_types=1);

use App\Livewire\CartActions;
use App\Livewire\CheckoutPage;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductField;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function (): void {
    Event::fake();

    $this->method = PaymentMethod::factory()->create();
    $this->product = Product::factory()->priced(50_000)->withStock(10)->create();
});

/**
 * Buy now is a shortcut through the cart, not a second checkout: the line lands in the
 * cart and the customer lands on checkout, where everything else is collected once.
 */
it('adds the line to the cart and redirects to checkout', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CartActions::class)
        ->dispatch('buy-now', slug: $this->product->slug)
        ->assertRedirect(route('checkout'));

    $cart = app(CartService::class)->current($user);

    expect($cart->items)->toHaveCount(1)
        ->and($cart->items->first()->product_id)->toBe($this->product->getKey())
        ->and($cart->items->first()->quantity)->toBe($this->product->min_qty);
});

it('keeps a guest\'s line and sends them to checkout, which asks them to sign in', function (): void {
    Livewire::test(CartActions::class)
        ->dispatch('buy-now', slug: $this->product->slug)
        ->assertRedirect(route('checkout'));

    expect(app(CartService::class)->current()->items)->toHaveCount(1);

    // Checkout is account-only: the guest is sent to sign in, and comes back here after.
    $this->get(route('checkout'))->assertRedirect(route('login'));
});

it('will not place an order for a guest', function (): void {
    app(CartService::class)->add($this->product, 1, [], 'guest@example.com');

    Livewire::test(CheckoutPage::class)
        ->set('email', 'guest@example.com')
        ->set('paymentKey', $this->method->key)
        ->set('sender', '01712345678')
        ->set('trx', 'ABC123XYZ')
        ->call('place')
        ->assertNoRedirect()
        ->assertSet('error', __('shop.sign_in_to_order'));

    expect(Order::query()->count())->toBe(0);
});

it('adds without leaving the page on quick add', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CartActions::class)
        ->dispatch('quick-add', slug: $this->product->slug)
        ->assertNoRedirect()
        ->assertDispatched('cart-updated');

    expect(app(CartService::class)->current($user)->items)->toHaveCount(1);
});

it('ignores a sold-out product', function (): void {
    $this->product->update(['stock' => 0]);

    Livewire::actingAs(User::factory()->create())
        ->test(CartActions::class)
        ->dispatch('buy-now', slug: $this->product->slug)
        ->assertNoRedirect();

    expect(app(CartService::class)->current()->items)->toHaveCount(0);
});

/**
 * The fields the modal used to ask for now live on the checkout page, and an order cannot
 * be placed while a required one is empty.
 */
it('will not place an order while a required product field is empty', function (): void {
    $field = ProductField::factory()->for($this->product)->create(['required' => true, 'label' => 'Player ID']);
    $user = User::factory()->create();

    app(CartService::class)->add($this->product, 1, [], $user->email, $user);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentKey', $this->method->key)
        ->set('sender', '01712345678')
        ->set('trx', 'ABC123XYZ')
        ->call('place')
        ->assertNoRedirect()
        ->assertSet('error', __('shop.field_required', ['field' => $field->label, 'product' => $this->product->name]));
});

it('stores the answers on the cart line and places the order', function (): void {
    $field = ProductField::factory()->for($this->product)->create(['required' => true, 'key' => 'player_id']);
    $user = User::factory()->create();
    $item = app(CartService::class)->add($this->product, 1, [], $user->email, $user);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentKey', $this->method->key)
        ->set('sender', '01712345678')
        ->set('trx', 'ABC123XYZ')
        ->set("lineFields.{$item->id}.player_id", '55512345')
        ->call('place')
        ->assertRedirectContains('/order/');

    expect(Order::query()->latest('id')->first()->items->first()->field_values)
        ->toBe(['player_id' => '55512345']);
});
