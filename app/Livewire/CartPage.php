<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CouponService;
use App\Support\Money;
use Livewire\Component;

/** The cart page: quantities, removal, the coupon and the running total. */
final class CartPage extends Component
{
    public string $coupon = '';

    public string $couponMsg = '';

    public bool $couponOk = false;

    public int $discount = 0;

    public function setQuantity(int $itemId, int $quantity, CartService $carts): void
    {
        $item = $this->ownedItem($itemId);

        if ($item === null) {
            return;
        }

        if ($quantity < 1) {
            $carts->remove($item);
        } else {
            $carts->setQuantity($item, $quantity);
        }

        $this->afterChange();
    }

    public function remove(int $itemId, CartService $carts): void
    {
        $item = $this->ownedItem($itemId);

        if ($item !== null) {
            $carts->remove($item);
            $this->afterChange();
        }
    }

    public function clear(CartService $carts): void
    {
        $carts->clear();
        $this->afterChange();
    }

    public function applyCoupon(CouponService $coupons, CartService $carts): void
    {
        $cart = $carts->current()->load('items.product');

        $result = $coupons->validate(
            $this->coupon,
            $cart->subtotal(),
            auth()->user(),
            auth()->user()?->email,
            $cart->items->map(fn (CartItem $item): array => [
                'product_id' => (int) $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => (int) ($item->product?->price ?? 0),
            ])->all(),
        );

        $this->couponOk = $result->ok;
        $this->couponMsg = $result->message;
        $this->discount = $result->discount;

        // Remembered for the checkout page so the customer does not retype it.
        session(['cart_coupon' => $result->ok ? mb_strtoupper(trim($this->coupon)) : null]);
    }

    public function money(int $paisa): string
    {
        return Money::format($paisa);
    }

    public function render(CartService $carts)
    {
        $cart = $carts->current()->load(['items.product.category']);
        $subtotal = $cart->subtotal();

        if ($this->couponOk) {
            // A changed basket can invalidate a coupon that was valid a moment ago.
            $this->applyCoupon(app(CouponService::class), $carts);
        }

        return view('livewire.cart-page', [
            'items' => $cart->items,
            'count' => $cart->count(),
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $this->discount),
        ]);
    }

    /** Only ever act on a line in the caller's own cart. */
    private function ownedItem(int $itemId): ?CartItem
    {
        $cart = app(CartService::class)->current();

        return $cart->items()->whereKey($itemId)->first();
    }

    private function afterChange(): void
    {
        $this->dispatch('cart-updated');
    }
}
