<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\SecuritySettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optionally pin the admin panel to a set of IPs or CIDR blocks.
 *
 * An empty list means no restriction, which is the default: a misconfigured allowlist that
 * locks the only administrator out of their own shop is worse than the risk it removes.
 */
final class EnforceAdminIpAllowlist
{
    public function __construct(private readonly SecuritySettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = array_values(array_filter(array_map(
            'trim',
            explode(',', $this->settings->admin_ip_allowlist),
        )));

        if ($allowlist === []) {
            return $next($request);
        }

        $ip = (string) $request->ip();

        if (IpUtils::checkIp($ip, $allowlist)) {
            return $next($request);
        }

        Log::channel('security')->warning('admin.ip_blocked', ['ip' => $ip, 'path' => $request->path()]);

        abort(403, 'This address is not allowed to reach the admin panel.');
    }
}
