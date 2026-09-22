<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides whether LiteSpeed may keep a copy of this response.
 *
 * The edge cache stores a whole page and replays it to the next visitor, which is only
 * safe when the HTML contains nothing that belongs to one person. The storefront's public
 * pages are built that way on purpose: the CSRF token, cart count, sign-in state and wallet
 * balance are left out of the markup and fetched afterwards from /storefront/state.
 *
 * Everything else — the cart, checkout, an order page, the auth screens, both panels — is
 * inherently per-visitor and is marked no-cache. The allowlist is by route name rather than
 * a deny-list, so a new route is private until somebody deliberately makes it public.
 */
final class CachePublicStorefront
{
    /**
     * Routes whose HTML is identical for every signed-out visitor.
     *
     * @var array<int, string>
     */
    private const CACHEABLE_ROUTES = [
        'home',
        'category.show',
        'product.show',
        'search',
        'blog.index',
        'blog.show',
        'page.show',
        'our-story',
        'sitemap',
        'sitemap.section',
        'robots',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCache($request, $response)) {
            $ttl = (int) config('storefront-cache.lscache.ttl', 300);
            $tag = (string) config('storefront-cache.lscache.tag', 'storefront');

            $response->headers->set('X-LiteSpeed-Cache-Control', "public,max-age={$ttl}");
            $response->headers->set('X-LiteSpeed-Tag', $tag);

            return $response;
        }

        $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');

        return $response;
    }

    private function shouldCache(Request $request, Response $response): bool
    {
        if (! config('storefront-cache.enabled', true) || ! config('storefront-cache.lscache.enabled', true)) {
            return false;
        }

        if (! $request->isMethodCacheable() || $response->getStatusCode() !== Response::HTTP_OK) {
            return false;
        }

        if (! in_array((string) $request->route()?->getName(), self::CACHEABLE_ROUTES, true)) {
            return false;
        }

        foreach (['web', 'admin'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return false;
            }
        }

        $varyCookie = (string) $request->cookie(config('storefront-cache.lscache.vary_cookie'));

        if (str_starts_with($varyCookie, 'bypass-')) {
            return false;
        }

        // A page carrying a flash message was rendered for one person's last action.
        if ($request->hasSession()) {
            $flashed = array_merge(
                (array) $request->session()->get('_flash.old', []),
                (array) $request->session()->get('_flash.new', []),
            );

            if ($flashed !== []) {
                return false;
            }
        }

        return true;
    }
}
