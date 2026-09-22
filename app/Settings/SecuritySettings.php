<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class SecuritySettings extends Settings
{
    /** Comma-separated IPs or CIDR blocks. Empty means no restriction. */
    public string $admin_ip_allowlist;

    public bool $force_two_factor_for_super_admin;

    public int $admin_session_timeout_minutes;

    public bool $daily_summary_email_enabled;

    public static function group(): string
    {
        return 'security';
    }
}
