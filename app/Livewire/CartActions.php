<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Exceptions\DomainException;
use App\Models\Product;
use App\Services\CartService;
use App\Services\TrackingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The two buttons on every product: "Buy now" and the "+" beside it.
 *
 * Neither opens anything. Both put the line in the cart, because the cart is the only
 * place an order is actually assembled — "Buy now" then sends the customer straight to
 * checkout, where the delivery email, anything the product requires and the payment route
 * are all collected once, on one screen, by CheckoutService's own rules.
 *
 * The component renders nothing. It exists so a stateless cached page can still take an
 * action that writes to the database, and it lives in the layout so every page has it.
 */
final class CartActions extends Component
{
    /** "Buy now": into the cart, then on to checkout. */
    #[On('buy-now')]
    public function buyNow(string $slug, CartService $carts): mixed
    {
        if (! $this->add($slug, $carts)) {
            return null;
        }

        return redirect()->route('checkout');
    }

    /** The "+" button: into the cart, and stay where you are. */
    #[On('quick-add')]
    public function quickAdd(string $slug, CartService $carts): void
    {
        $this->add($slug, $carts);
    }

    public function render(): View
    {
        return view('livewire.cart-actions');
    }

    /**
     * Adds one line and says whether it worked.
     *
     * The quantity is the product's own minimum — the cart page and the checkout summary
     * are where a quantity gets changed. A guest has no delivery email yet and the product
     * may have required fields; both are asked for at checkout, so an incomplete line is
     * expected here rather than an error.
     */
    private function add(string $slug, CartService $carts): bool
    {
        $product = Product::active()->where('slug', $slug)->first();

        if ($product === null || ! $product->isInStock()) {
            $this->dispatch('toast', message: __('shop.no_longer_available'));

            return false;
        }

        try {
            $carts->add($product, $product->min_qty, [], auth()->user()?->email);
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return false;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: __('shop.added_to_cart', ['product' => $product->name]));
        $this->dispatch('track', name: 'add_to_cart', payload: app(TrackingService::class)->product($product, $product->min_qty));

        return true;
    }
}
