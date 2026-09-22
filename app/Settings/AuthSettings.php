<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class AuthSettings extends Settings
{
    public bool $google_login_enabled;

    public string $google_client_id;

    public string $google_client_secret;

    public bool $require_email_verification;

    public bool $registration_enabled;

    public static function group(): string
    {
        return 'auth';
    }

    public static function encrypted(): array
    {
        return ['google_client_secret'];
    }
}
