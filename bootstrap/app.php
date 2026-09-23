<?php

use App\Http\Middleware\CachePublicStorefront;
use App\Http\Middleware\CaptureReferral;
use App\Http\Middleware\CaptureSource;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLiteSpeedVaryCookie;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            SetLocale::class,
            CaptureSource::class,
            CaptureReferral::class,
            SetLiteSpeedVaryCookie::class,
            CachePublicStorefront::class,
        ]);

        // shurjoPay posts here from its own servers; there is no session and no token.
        // The payload is not trusted either way — it only names a payment to verify.
        $middleware->validateCsrfTokens(except: [
            'payments/shurjopay/ipn',
        ]);

        $middleware->trustProxies(at: '*');

        /*
         | The LiteSpeed vary cookie must not be encrypted.
         |
         | Laravel encrypts with a random IV, so the same plaintext produces a different
         | ciphertext for every visitor. LiteSpeed varies its page cache on the cookie's
         | literal value, so an encrypted vary cookie would give every anonymous visitor a
         | private cache entry and the edge cache would never serve a hit. The cookie holds
         | nothing secret — "guest:bn", or the name of the guard — and it is never trusted
         | for authorisation.
         */
        $middleware->encryptCookies(except: [
            SetLiteSpeedVaryCookie::COOKIE,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
