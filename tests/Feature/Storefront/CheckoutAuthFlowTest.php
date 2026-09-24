<?php

declare(strict_types=1);

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;

/**
 * Signing in from the checkout door.
 *
 * Checkout is account-only, so a guest who gets that far is sent to sign in — and has to
 * come back to checkout afterwards, with the basket they built as a guest. Signing in from
 * anywhere else still lands on the customer dashboard, which is what an account holder
 * arriving by the front door expects.
 */
/*
 | No Event::fake() here. Eloquent model events travel on the same dispatcher, and faking
 | them stops User::creating from issuing a referral code — which makes registration fail
 | on a NOT NULL column for reasons that have nothing to do with what is being tested.
 */
beforeEach(function (): void {
    $this->product = Product::factory()->priced(50_000)->withStock(5)->create();
});

it('sends a guest from checkout to sign in, then back to checkout', function (): void {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $this->get(route('checkout'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password123'])
        ->assertRedirect(route('checkout'));
});

it('sends a guest from checkout to registration, then back to checkout, signed in', function (): void {
    $this->get(route('checkout'))->assertRedirect(route('login'));

    $this->post(route('register.store'), [
        'name' => 'New Buyer',
        'email' => 'buyer@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => '1',
    ])->assertRedirect(route('checkout'));

    $this->assertAuthenticated();
});

it('folds the basket a guest built into their account', function (): void {
    $user = User::factory()->create();
    $carts = app(CartService::class);

    // What FortifyServiceProvider hands to the merge at sign-in is the session the guest
    // browsed with; the cookie the browser sends back is what keeps the two the same.
    $carts->add($this->product, 2);
    $guestSession = session()->getId();

    $carts->merge($user, $guestSession);

    expect($carts->current($user)->items)->toHaveCount(1)
        ->and($carts->current($user)->items->first()->quantity)->toBe(2)
        ->and(Cart::where('session_id', $guestSession)->whereNull('user_id')->exists())->toBeFalse();
});

it('lands on the customer dashboard when sign-in did not start at checkout', function (): void {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password123'])
        ->assertRedirect(config('fortify.home'));
});
