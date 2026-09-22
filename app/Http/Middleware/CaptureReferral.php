<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AffiliateService;
use App\Settings\AffiliateSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns ?ref=CODE into a cookie and a visit record.
 *
 * The visit is only logged the first time the cookie is set, so a referred customer
 * browsing twenty pages counts as one click, not twenty.
 */
final class CaptureReferral
{
    public function __construct(
        private readonly AffiliateSettings $settings,
        private readonly AffiliateService $affiliate,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $code = trim((string) $request->query('ref', ''));

        if ($code === '' || ! $this->settings->enabled) {
            return $next($request);
        }

        $alreadyTracked = $request->cookie('ref') === $code;

        Cookie::queue('ref', $code, 60 * 24 * $this->settings->cookie_days);

        if (! $alreadyTracked) {
            $referrer = User::where('referral_code', mb_strtoupper($code))->first();

            if ($referrer !== null) {
                $this->affiliate->recordVisit(
                    $referrer,
                    $request->ip(),
                    (string) $request->userAgent(),
                    $request->fullUrl(),
                );
            }
        }

        return $next($request);
    }
}
