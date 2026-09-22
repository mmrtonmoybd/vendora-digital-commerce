<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

/** Just the number, for the mobile drawer's "My Cart (n)". */
final class CartCount extends Component
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
        return view('livewire.cart-count');
    }
}
