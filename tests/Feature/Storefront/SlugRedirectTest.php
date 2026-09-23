<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\SlugRedirect;

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
