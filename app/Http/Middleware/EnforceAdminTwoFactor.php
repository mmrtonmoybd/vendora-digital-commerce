<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\SecuritySettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the setting is on, a super admin must finish two-factor setup before the panel will
 * show them anything other than their own profile page.
 */
final class EnforceAdminTwoFactor
{
    public function __construct(private readonly SecuritySettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->force_two_factor_for_super_admin) {
            return $next($request);
        }

        $admin = $request->user('admin');

        if ($admin === null || ! $admin->isSuperAdmin() || $admin->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        $profileUrl = url('/admin/profile');

        if ($request->fullUrlIs($profileUrl.'*') || $request->routeIs('filament.admin.auth.logout')) {
            return $next($request);
        }

        return redirect($profileUrl)
            ->with('error', 'Two-factor authentication is required for super admins. Please enable it to continue.');
    }
}
