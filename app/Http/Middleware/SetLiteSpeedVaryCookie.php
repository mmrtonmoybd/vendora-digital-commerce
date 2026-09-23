<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Currencies;
use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells LiteSpeed which cookie decides who gets which cached copy of a page.
 *
 * On a single-language site anonymous visitors are deliberately left cookie-free so they
 * all share one cached variant — otherwise every first-time visitor and every crawler would
 * miss the cache. A signed-in customer gets their guard name, and a request carrying a flash
 * message gets a one-off value so the flash is never baked into a shared page.
 *
 * Once a second language or a second display currency is enabled that stops being safe:
 * the same URL now renders differently for a Bengali reader and an English one, and shows
 * different prices in dollars than in taka. Both join the cookie, and anonymous visitors
 * split into one cached variant per combination.
 */
final class SetLiteSpeedVaryCookie
{
    /**
     * The cookie's name.
     *
     * A constant as well as a config value because middleware registration happens in
     * bootstrap/app.php, before the config repository exists.
     */
    public const COOKIE = '_lscache_vary_vendora';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $cookieName = (string) config('storefront-cache.lscache.vary_cookie');
        $cookieValue = $this->authenticatedGuard();

        if (Locales::isMultilingual()) {
            $cookieValue = ($cookieValue ?? 'guest').':'.app()->getLocale();
        }

        // Prices differ per currency, so a cached page is only reusable by someone browsing
        // in the same one.
        if (Currencies::isMultiCurrency()) {
            $cookieValue = ($cookieValue ?? 'guest').':'.Currencies::current();
        }

        if ($request->hasSession() && (array) $request->session()->get('_flash.new', []) !== []) {
            $cookieValue = 'bypass-'.mb_substr(hash('sha256', $request->session()->getId()), 0, 20);
        }

        if ($cookieValue === null) {
            if ($request->cookies->has($cookieName)) {
                $response->headers->setCookie(Cookie::forget(
                    $cookieName,
                    (string) config('session.path', '/'),
                    config('session.domain'),
                ));
            }
        } else {
            $response->headers->setCookie(Cookie::make(
                $cookieName,
                $cookieValue,
                (int) config('session.lifetime', 120),
                (string) config('session.path', '/'),
                config('session.domain'),
                (bool) config('session.secure', false),
                true,
                false,
                (string) config('session.same_site', 'lax'),
            ));
        }

        $response->headers->set('X-LiteSpeed-Vary', 'cookie='.$cookieName);

        if ($request->is('admin', 'admin/*', 'customer', 'customer/*')) {
            $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
        }

        return $response;
    }

    private function authenticatedGuard(): ?string
    {
        foreach (['web', 'admin'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }
}
