<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The portal has no login screen of its own.
 *
 * Sending guests to the storefront's login keeps one sign-in experience, in the template's
 * design, and means a customer who follows an emailed portal link lands back where they
 * were once they authenticate.
 */
final class RedirectGuestsToStorefrontLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login');
        }

        if ($user->status !== UserStatus::Active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been suspended. Please contact support.',
            ]);
        }

        return $next($request);
    }
}
