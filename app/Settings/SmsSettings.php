<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * A generic HTTP SMS driver: the admin pastes their provider's endpoint and parameter
 * template, so no provider is hard-coded into the application.
 */
final class SmsSettings extends Settings
{
    public bool $enabled;

    public string $endpoint;

    /** GET | POST */
    public string $method;

    public string $api_key;

    public string $sender_id;

    /** JSON map of query/body params; {to} and {message} are substituted. */
    public string $params_template;

    public bool $on_order_completed;

    public bool $on_deposit_approved;

    public static function group(): string
    {
        return 'sms';
    }

    public static function encrypted(): array
    {
        return ['api_key'];
    }
}
