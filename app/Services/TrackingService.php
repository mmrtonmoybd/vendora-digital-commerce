<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Settings\SeoSettings;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Builds the payloads the advertising pixels expect.
 *
 * Meta, TikTok and Google each name the same commerce events slightly differently. One
 * payload is assembled here from the real models and mapped per platform in the browser,
 * so a price can never drift between what the shop charged and what the pixel reported.
 *
 * Values are sent in whole currency units (taka), because that is what the ad platforms
 * expect; the ledger stays in paisa.
 */
final class TrackingService
{
    public function __construct(private readonly SeoSettings $settings) {}

    public function isEnabled(): bool
    {
        return $this->settings->meta_pixel_id !== ''
            || $this->settings->tiktok_pixel_id !== ''
            || $this->settings->ga4_id !== ''
            || $this->settings->gtm_id !== '';
    }

    /**
     * A single product, for ViewContent and AddToCart.
     *
     * @return array<string, mixed>
     */
    public function product(Product $product, int $quantity = 1): array
    {
        return [
            'content_id' => $product->slug,
            'content_name' => $product->name,
            'content_type' => 'product',
            'content_category' => $product->category?->name,
            'quantity' => $quantity,
            'price' => $this->units($product->price),
            'value' => $this->units($product->price * $quantity),
            'currency' => Money::currencyCode(),
        ];
    }

    /**
     * A basket or an order, for InitiateCheckout and Purchase.
     *
     * @param  array<int, array{slug: ?string, name: string, quantity: int, unit_price: int}>  $lines
     * @return array<string, mixed>
     */
    public function basket(array $lines, int $value, ?string $eventId = null): array
    {
        return [
            'contents' => array_map(fn (array $line): array => [
                'content_id' => $line['slug'] ?? Str::slug($line['name']),
                'content_name' => $line['name'],
                'quantity' => $line['quantity'],
                'price' => $this->units($line['unit_price']),
            ], $lines),
            'content_type' => 'product',
            'num_items' => array_sum(array_column($lines, 'quantity')),
            'value' => $this->units($value),
            'currency' => Money::currencyCode(),
            'event_id' => $eventId ?? $this->eventId('checkout'),
        ];
    }

    /**
     * The Purchase payload for a completed order.
     *
     * The event id is derived from the order number, so a customer who reloads the success
     * page does not report a second sale.
     *
     * @return array<string, mixed>
     */
    public function purchase(Order $order): array
    {
        $lines = $order->items->map(fn ($item): array => [
            'slug' => $item->slug(),
            'name' => $item->name(),
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->all();

        return $this->basket($lines, $order->total, 'purchase-'.$order->order_number) + [
            'order_id' => $order->order_number,
        ];
    }

    /** @return array<string, mixed> */
    public function search(string $term): array
    {
        return [
            'search_string' => $term,
            'currency' => Money::currencyCode(),
        ];
    }

    /** A per-event id, used to de-duplicate browser and server copies of the same event. */
    public function eventId(string $prefix): string
    {
        return $prefix.'-'.Str::lower(Str::random(16));
    }

    private function units(int $paisa): float
    {
        return round($paisa / 100, 2);
    }
}
