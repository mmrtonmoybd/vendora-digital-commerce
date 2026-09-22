<?php

use App\Http\Middleware\CachePublicStorefront;
use App\Http\Middleware\CaptureReferral;
use App\Http\Middleware\CaptureSource;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLiteSpeedVaryCookie;
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
