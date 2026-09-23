<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
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

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https:'];
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'];
        $connectSrc = ["'self'", 'https:'];
        $imgSrc = ["'self'", 'data:', 'blob:', 'https:'];

        /*
         | Development only: let the Vite dev server through.
         |
         | `npm run dev` serves the bundle from its own origin (http://[::1]:5173 by
         | default), which "'self'" does not cover — so the CSP blocked the stylesheet and
         | the entry script, and the storefront rendered unstyled with no Alpine stores.
         | The hot file exists only while that server is running, so this widens nothing
         | once the assets are built.
         */
        foreach ($this->viteDevServerOrigins() as $origin) {
            $scriptSrc[] = $origin;
            $styleSrc[] = $origin;
            $connectSrc[] = $origin;
            $imgSrc[] = $origin;
        }

        // Also development only: a local app is routinely reached on a host that is not the
        // one in APP_URL — 127.0.0.1 against localhost, say — which makes its own uploaded
        // images cross-origin and blocks every product thumbnail.
        if (! app()->environment('production')) {
            $imgSrc[] = 'http:';
            $connectSrc[] = 'ws:';
            $connectSrc[] = 'http:';
        }

        return implode('; ', [
            "default-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self' https://sandbox.shurjopayment.com https://engine.shurjopayment.com",
            'img-src '.implode(' ', $imgSrc),
            "font-src 'self' data: https://fonts.gstatic.com",
            'style-src '.implode(' ', $styleSrc),
            // Pixel and tag-manager snippets are injected inline by design.
            'script-src '.implode(' ', $scriptSrc),
            'connect-src '.implode(' ', $connectSrc),
        ]);
    }

    /**
     * The origins the running Vite dev server is reachable on, or none.
     *
     * The hot file holds one URL, but the browser may resolve the same server over IPv6 or
     * IPv4 depending on how the page itself was reached, so both loopback forms are
     * allowed along with whatever the file names. The websocket origins are needed for HMR.
     *
     * @return array<int, string>
     */
    private function viteDevServerOrigins(): array
    {
        if (app()->environment('production') || ! is_file(Vite::hotFile())) {
            return [];
        }

        $url = trim((string) file_get_contents(Vite::hotFile()));

        if ($url === '') {
            return [];
        }

        $port = parse_url($url, PHP_URL_PORT) ?: 5173;
        $host = (string) parse_url($url, PHP_URL_HOST);

        $origins = ['http://localhost:', 'ws://localhost:', 'http://127.0.0.1:', 'ws://127.0.0.1:'];
        $origins = array_map(fn (string $prefix): string => $prefix.$port, $origins);

        /*
         | An IPv6 literal cannot appear in a CSP source list — the browser discards the
         | whole entry as invalid rather than matching it. vite.config.js pins the dev
         | server to 127.0.0.1 for exactly this reason, but if someone overrides that, the
         | host is only added when it is expressible.
         */
        if ($host !== '' && ! str_contains($host, ':')) {
            $origins[] = 'http://'.$host.':'.$port;
            $origins[] = 'ws://'.$host.':'.$port;
        }

        return array_values(array_unique($origins));
    }
}
