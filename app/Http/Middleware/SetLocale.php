<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locales;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides which language this request is answered in.
 *
 * The order matters and runs from most deliberate to least:
 *
 *   1. an explicit choice this request is making (?lang=bn), which is also remembered;
 *   2. what this person chose on their account, which follows them to a new device;
 *   3. what this browser chose earlier in the session;
 *   4. what the browser says it prefers, if the administrator left detection on;
 *   5. the configured default.
 *
 * A locale that is not enabled is ignored at every step rather than honoured, so turning a
 * language off in the panel genuinely turns it off — including for the people who had
 * already picked it.
 *
 * Only a deliberate choice is written to the session. A *detected* locale is recomputed on
 * every request instead, so that a visitor is never silently pinned to a guess — and so the
 * administrator changing the default language takes effect for everyone who has not
 * actually chosen one.
 */
final class SetLocale
{
    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $requested = $request->query('lang');

        if (is_string($requested) && Locales::isEnabled($requested)) {
            $this->remember($request, $requested);

            return $requested;
        }

        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user();

        if ($user !== null && Locales::isEnabled($user->locale)) {
            return (string) $user->locale;
        }

        if ($request->hasSession()) {
            $session = $request->session()->get(self::SESSION_KEY);

            if (is_string($session) && Locales::isEnabled($session)) {
                return $session;
            }
        }

        if (Locales::autoDetects()) {
            $detected = Locales::fromAcceptLanguage($request->header('Accept-Language'));

            if ($detected !== null) {
                return $detected;
            }
        }

        return Locales::default();
    }

    /** Persist a deliberate choice against the account as well as the session. */
    private function remember(Request $request, string $locale): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $locale);
        }

        foreach (['web', 'admin'] as $guard) {
            $user = Auth::guard($guard)->user();

            if ($user !== null && $user->locale !== $locale) {
                $user->forceFill(['locale' => $locale])->saveQuietly();
            }
        }
    }
}
