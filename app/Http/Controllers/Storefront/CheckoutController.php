<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Settings\CommerceSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CommerceSettings $settings,
    ) {}

    public function index(): View|RedirectResponse
    {
        if (auth()->guest() && ! $this->settings->guest_checkout_enabled) {
            session()->put('url.intended', route('checkout'));

            return redirect()->route('login');
        }

        if ($this->carts->count() === 0) {
            return redirect()->route('cart');
        }

        return view('pages.checkout');
    }
}
