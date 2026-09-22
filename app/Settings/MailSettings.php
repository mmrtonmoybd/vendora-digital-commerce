<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** SMTP credentials entered by the admin; applied to the mailer at runtime. */
final class MailSettings extends Settings
{
    /** smtp | log | sendmail */
    public string $mailer;

    public string $host;

    public int $port;

    public string $username;

    public string $password;

    /** tls | ssl | null */
    public ?string $encryption;

    public string $from_address;

    public string $from_name;

    public static function group(): string
    {
        return 'mail';
    }

    public static function encrypted(): array
    {
        return ['password'];
    }
}
