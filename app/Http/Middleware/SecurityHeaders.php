<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response headers.
 *
 * The CSP is deliberately permissive about script sources: the shop loads Meta, TikTok and
 * Google pixels, and the admin pastes raw marketing snippets into settings. Locking those
 * down would break the tracking the business runs on, so the policy concentrates on what
 * it can usefully prevent — framing, MIME sniffing and referrer leakage.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), interest-cohort=()',
        );

        if (app()->environment('production') && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self' https://sandbox.shurjopayment.com https://engine.shurjopayment.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            // Pixel and tag-manager snippets are injected inline by design.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:",
            "connect-src 'self' https:",
        ]));

        return $response;
    }
}
