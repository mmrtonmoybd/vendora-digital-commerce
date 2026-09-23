<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use App\Support\Currencies;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The language switch.
 *
 * A GET route rather than a posted form, and deliberately so: the public pages are served
 * from the edge cache with no CSRF token in the HTML — the token is fetched afterwards by
 * the hydration script — so a posted switcher would be dead for anyone whose JavaScript has
 * not run yet, which includes the first paint of every cached page.
 *
 * Nothing destructive happens here, and an unknown or disabled locale is ignored rather
 * than rejected: someone following a stale link to a language that has since been turned
 * off should land on the site, not on an error.
 */
final class LocaleController extends Controller
{
    /** Switch the display currency. Same reasoning as the language switch above. */
    public function currency(Request $request, string $currency): RedirectResponse
    {
        if (Currencies::isEnabled($currency)) {
            $request->session()->put(SetCurrency::SESSION_KEY, $currency);

            $user = Auth::guard('web')->user();

            if ($user !== null) {
                $user->forceFill(['currency' => $currency])->saveQuietly();
            }
        }

        return redirect()->to($this->backTo($request));
    }

    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (Locales::isEnabled($locale)) {
            $request->session()->put(SetLocale::SESSION_KEY, $locale);

            foreach (['web', 'admin'] as $guard) {
                $user = Auth::guard($guard)->user();

                if ($user !== null) {
                    $user->forceFill(['locale' => $locale])->saveQuietly();
                }
            }
        }

        return redirect()->to($this->backTo($request));
    }

    /**
     * Where to send them next.
     *
     * Only same-host referrers are honoured — an open redirect on a route this public would
     * be a gift to anyone building a phishing link.
     */
    private function backTo(Request $request): string
    {
        $previous = $request->headers->get('referer');

        if ($previous === null) {
            return route('home');
        }

        $host = parse_url($previous, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return route('home');
        }

        return $previous;
    }
}
