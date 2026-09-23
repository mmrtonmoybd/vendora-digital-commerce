<?php

use App\Http\Controllers\Storefront\SlugRedirectController;
use App\Http\Middleware\CachePublicStorefront;
use App\Http\Middleware\CaptureReferral;
use App\Http\Middleware\CaptureSource;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLiteSpeedVaryCookie;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            SetCurrency::class,
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

        /*
         | Before showing a 404, check whether this address used to belong to something that
         | has since been renamed.
         |
         | Handled here rather than as a fallback route because the common case is not "no
         | route matched" — /product/{product:slug} matches perfectly well, it is the model
         | binding that finds nothing. A fallback route would never see it.
         */
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->isMethodCacheable()) {
                return null;
            }

            return app(SlugRedirectController::class)->resolve($request);
        });
    })->create();
