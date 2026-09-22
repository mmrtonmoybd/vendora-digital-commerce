<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Everything about the current visitor that the cached HTML cannot know.
 *
 * The storefront's public pages are cached whole at the edge by LiteSpeed, so one visitor's
 * HTML is served to the next. Anything personal — the CSRF token, the cart count, whether
 * they are signed in, their wallet balance — is therefore deliberately absent from that
 * HTML and fetched from here instead, once, after the page paints.
 *
 * This response is never cached.
 */
final class StorefrontStateController extends Controller
{
    public function __invoke(Request $request, CartService $carts): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            // Livewire reads the csrf-token meta tag on every request, so refreshing that
            // tag is enough to make components work on a page served from the edge cache.
            'csrf' => csrf_token(),
            'cart_count' => $carts->count(),
            'authenticated' => $user !== null,
            'user' => $user === null ? null : [
                'name' => $user->name,
                'email' => $user->email,
                'balance' => Money::format((int) $user->balance),
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-LiteSpeed-Cache-Control' => 'no-cache',
        ]);
    }
}
