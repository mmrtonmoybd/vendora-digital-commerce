<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Settings\CommerceSettings;
use Illuminate\Support\Facades\DB;

/**
 * One cart per visitor, keyed by session for guests and by user id once signed in.
 *
 * Quantities are clamped against stock and the product's own min/max on every write, so a
 * cart that sat open for a week cannot check out more than is actually available.
 */
final class CartService
{
    public function __construct(private readonly CommerceSettings $settings) {}

    public function current(?User $user = null, ?string $sessionId = null): Cart
    {
        $user ??= auth()->user();
        $sessionId ??= session()->getId();

        if ($user !== null) {
            return Cart::firstOrCreate(['user_id' => $user->getKey()]);
        }

        return Cart::firstOrCreate(['session_id' => $sessionId, 'user_id' => null]);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function add(Product $product, int $quantity, array $fields = [], ?string $deliveryEmail = null, ?User $user = null): CartItem
    {
        $cart = $this->current($user);

        return DB::transaction(function () use ($cart, $product, $quantity, $fields, $deliveryEmail): CartItem {
            // Identical field values mean it is the same line; anything else is a new one,
            // because two game top-ups for different player IDs are not interchangeable.
            $existing = $cart->items()
                ->where('product_id', $product->getKey())
                ->get()
                ->first(fn (CartItem $item): bool => $item->fields == $fields);

            if ($existing !== null) {
                $existing->update([
                    'quantity' => $this->clamp($product, $existing->quantity + $quantity),
                    'delivery_email' => $deliveryEmail ?: $existing->delivery_email,
                ]);

                return $existing;
            }

            if ($cart->items()->count() >= $this->settings->max_cart_items) {
                throw new \App\Exceptions\DomainException('Your cart is full.');
            }

            return $cart->items()->create([
                'product_id' => $product->getKey(),
                'quantity' => $this->clamp($product, $quantity),
                'fields' => $fields,
                'delivery_email' => $deliveryEmail,
            ]);
        });
    }

    public function setQuantity(CartItem $item, int $quantity): void
    {
        $product = $item->product;

        if ($product === null) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $this->clamp($product, $quantity)]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(?User $user = null): void
    {
        $this->current($user)->items()->delete();
    }

    /**
     * Fold a guest's session cart into the user's cart at login.
     *
     * The guest cart is the newer intent, so its quantities win, still clamped to stock.
     */
    public function merge(User $user, string $sessionId): void
    {
        $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();

        if ($guestCart === null) {
            return;
        }

        DB::transaction(function () use ($guestCart, $user): void {
            $userCart = Cart::firstOrCreate(['user_id' => $user->getKey()]);

            foreach ($guestCart->items()->with('product')->get() as $item) {
                if ($item->product === null) {
                    continue;
                }

                $match = $userCart->items()
                    ->where('product_id', $item->product_id)
                    ->get()
                    ->first(fn (CartItem $existing): bool => $existing->fields == $item->fields);

                if ($match !== null) {
                    $match->update(['quantity' => $this->clamp($item->product, $match->quantity + $item->quantity)]);

                    continue;
                }

                $userCart->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $this->clamp($item->product, $item->quantity),
                    'fields' => $item->fields,
                    'delivery_email' => $item->delivery_email ?: $user->email,
                ]);
            }

            $guestCart->items()->delete();
            $guestCart->delete();
        });
    }

    /**
     * Problems that must block checkout, in the template's own wording.
     *
     * @return array<int, string>
     */
    public function validateLines(Cart $cart): array
    {
        $errors = [];

        foreach ($cart->items()->with('product')->get() as $item) {
            $product = $item->product;

            if ($product === null || ! $product->is_active) {
                $errors[] = 'One of the products in your cart is no longer available.';

                continue;
            }

            if ($product->stock < $item->quantity) {
                $errors[] = "{$product->name} only has {$product->stock} left in stock.";
            }

            if ($item->quantity < $product->min_qty) {
                $errors[] = "{$product->name} has a minimum order of {$product->min_qty}.";
            }
        }

        return array_values(array_unique($errors));
    }

    public function count(?User $user = null): int
    {
        return (int) $this->current($user)->items()->sum('quantity');
    }

    private function clamp(Product $product, int $quantity): int
    {
        $max = $product->maxOrderableQty();

        if ($max < 1) {
            return $product->min_qty;
        }

        return max($product->min_qty, min($quantity, $max));
    }
}
