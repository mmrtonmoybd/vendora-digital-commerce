<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Live gateway credentials. Nothing here has a hard-coded fallback in application code:
 * the admin enters the values and can move between sandbox and live without a deploy.
 */
final class ShurjopaySettings extends Settings
{
    public bool $enabled;

    /** sandbox | live */
    public string $mode;

    public string $api_url;

    public string $username;

    public string $password;

    /** shurjoPay allows at most 5 characters. */
    public string $prefix;

    /** BDT | USD */
    public string $currency;

    public bool $ssl_verify;

    /** Optional surcharge in basis points added to the gateway amount. */
    public int $fee_bps;

    public string $customer_city;

    public string $customer_postcode;

    public string $customer_country;

    public static function group(): string
    {
        return 'shurjopay';
    }

    public static function encrypted(): array
    {
        return ['password'];
    }

    public function isSandbox(): bool
    {
        return $this->mode !== 'live';
    }
}
