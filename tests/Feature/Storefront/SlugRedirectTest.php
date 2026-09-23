<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SlugRedirect;
use App\Models\User;
use Illuminate\Support\Facades\URL;

it('keeps the old address working when a product is renamed', function (): void {
    $product = Product::factory()->create(['slug' => 'stream-premium']);

    $product->update(['slug' => 'stream-premium-1m']);

    $this->get('/product/stream-premium')
        ->assertRedirect('/product/stream-premium-1m')
        ->assertStatus(301);
});

it('does the same for a category', function (): void {
    $category = Category::factory()->create(['slug' => 'streaming']);

    $category->update(['slug' => 'streaming-services']);

    $this->get('/category/streaming')->assertRedirect('/category/streaming-services');
});

it('leaves a genuinely unknown address as a 404', function (): void {
    $this->get('/product/never-existed')->assertNotFound();
});

it('does not build a chain when something is renamed twice', function (): void {
    $product = Product::factory()->create(['slug' => 'one']);

    $product->update(['slug' => 'two']);
    $product->update(['slug' => 'three']);

    // Both old addresses point straight at the live one; a crawler follows one hop, not two.
    expect(SlugRedirect::where('from_path', '/product/one')->value('to_path'))->toBe('/product/three')
        ->and(SlugRedirect::where('from_path', '/product/two')->value('to_path'))->toBe('/product/three');

    $this->get('/product/one')->assertRedirect('/product/three');
});

it('clears the redirect when a slug is renamed back', function (): void {
    $product = Product::factory()->create(['slug' => 'one']);

    $product->update(['slug' => 'two']);
    $product->update(['slug' => 'one']);

    // Otherwise /product/one would redirect away from the page that now lives there.
    expect(SlugRedirect::where('from_path', '/product/one')->exists())->toBeFalse();

    $this->get('/product/one')->assertOk();
});

it('counts how often an old address is still being used', function (): void {
    $product = Product::factory()->create(['slug' => 'one']);
    $product->update(['slug' => 'two']);

    $this->get('/product/one');
    $this->get('/product/one');

    expect(SlugRedirect::where('from_path', '/product/one')->value('hits'))->toBe(2);
});

it('ignores an edit that does not touch the slug', function (): void {
    $product = Product::factory()->create(['slug' => 'one', 'name' => 'One']);

    $product->update(['name' => 'One renamed']);

    expect(SlugRedirect::count())->toBe(0);
});

/**
 * The "I didn't get the invite" page.
 *
 * Reached from a signed link in the delivery email, so it must render for a guest with no
 * session. Its view did not exist at all — the link in every access email threw.
 */
it('renders the access problem form from a signed link', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    $this->get(URL::signedRoute('access.problem', ['item' => $item->id]))
        ->assertOk()
        ->assertSee(__('access.what_happened'))
        ->assertSee($order->order_number);
});

it('refuses the access problem form without a signature', function (): void {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    $this->get(route('access.problem', ['item' => $item->id]))->assertForbidden();
});
