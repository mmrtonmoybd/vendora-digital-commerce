<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Currencies;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Which currency this visitor sees prices in.
 *
 * Storefront only. The admin panel, the reports, the ledger and the invoice all read the
 * base currency, because that is the number that actually moved — a back office looking at
 * converted figures would be reconciling against a rate instead of against the bank.
 *
 * Resolution mirrors the language switch: an explicit choice, then the account, then the
 * session, then the configured default. There is no browser detection, because a browser
 * says nothing about which currency somebody wants to be quoted in.
 */
final class SetCurrency
{
    public const SESSION_KEY = 'currency';

    public function handle(Request $request, Closure $next): Response
    {
        // Set unconditionally, including back to the default: the value is process-static,
        // and a stale one leaking into the next request would misprice a page.
        Currencies::setCurrent($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $requested = $request->query('currency');

        if (is_string($requested) && Currencies::isEnabled($requested)) {
            $this->remember($request, $requested);

            return $requested;
        }

        $user = Auth::guard('web')->user();

        if ($user !== null && Currencies::isEnabled($user->currency)) {
            return (string) $user->currency;
        }

        if ($request->hasSession()) {
            $session = $request->session()->get(self::SESSION_KEY);

            if (is_string($session) && Currencies::isEnabled($session)) {
                return $session;
            }
        }

        return Currencies::default();
    }

    private function remember(Request $request, string $currency): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $currency);
        }

        $user = Auth::guard('web')->user();

        if ($user !== null && $user->currency !== $currency) {
            $user->forceFill(['currency' => $currency])->saveQuietly();
        }
    }
}
