<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks a storefront response cacheable at the edge, or explicitly not.
 *
 * The default is not: a page is only cached when it is a plain GET, returned 200, belongs
 * to nobody in particular, and carries no flash message. Anything else — a cart, a
 * validation error, a signed-in header — must never be handed to the next visitor.
 */
final class CachePublicStorefront
{
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

        foreach (['web', 'admin'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return false;
            }
        }

        $varyCookie = (string) $request->cookie(config('storefront-cache.lscache.vary_cookie'));

        if (str_starts_with($varyCookie, 'bypass-')) {
            return false;
        }

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
