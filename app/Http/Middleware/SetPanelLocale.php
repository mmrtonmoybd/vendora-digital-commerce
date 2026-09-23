<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\LocaleSettings;
use App\Support\Locales;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The language inside a Filament panel.
 *
 * Separate from SetLocale because the panels do not run the `web` middleware group, and
 * because each panel is opt-in: an administrator may reasonably want the shop bilingual
 * while the back office stays in one language, and a shared staff machine switching
 * language under someone is worse than an untranslated label.
 *
 * Whether it applies at all is the `apply_to_customer_panel` / `apply_to_admin_panel`
 * setting; when it does not, the panel is served in the default locale rather than in
 * whatever the last storefront page happened to be.
 */
final class SetPanelLocale
{
    public function handle(Request $request, Closure $next, string $panel = 'customer'): Response
    {
        $locale = $this->applies($panel)
            ? $this->resolve($request)
            : Locales::default();

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        return $next($request);
    }

    private function applies(string $panel): bool
    {
        try {
            $settings = app(LocaleSettings::class);

            return $panel === 'admin'
                ? $settings->apply_to_admin_panel
                : $settings->apply_to_customer_panel;
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolve(Request $request): string
    {
        $requested = $request->query('lang');

        if (is_string($requested) && Locales::isEnabled($requested)) {
            $request->session()->put(SetLocale::SESSION_KEY, $requested);

            foreach (['web', 'admin'] as $guard) {
                $user = Auth::guard($guard)->user();

                if ($user !== null) {
                    $user->forceFill(['locale' => $requested])->saveQuietly();
                }
            }

            return $requested;
        }

        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user();

        if ($user !== null && Locales::isEnabled($user->locale)) {
            return (string) $user->locale;
        }

        $session = $request->hasSession() ? $request->session()->get(SetLocale::SESSION_KEY) : null;

        if (is_string($session) && Locales::isEnabled($session)) {
            return $session;
        }

        return Locales::default();
    }
}
