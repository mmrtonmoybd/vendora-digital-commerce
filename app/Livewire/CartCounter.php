<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

/** The badge on the header's cart button. */
final class CartCounter extends Component
{
    public int $count = 0;

    public function mount(CartService $carts): void
    {
        $this->count = $carts->count();
    }

    #[On('cart-updated')]
    public function refreshCount(CartService $carts): void
    {
        $this->count = $carts->count();
    }

    public function render()
    {
        return view('livewire.cart-counter');
    }
}
