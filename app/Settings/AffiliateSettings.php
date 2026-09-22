<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class AffiliateSettings extends Settings
{
    public bool $enabled;

    /** percent | fixed */
    public string $commission_type;

    /** Whole percent when type is percent, paisa when fixed. */
    public int $commission_value;

    /** first_order | every_order */
    public string $trigger;

    /** auto_on_complete | manual */
    public string $approval;

    public int $min_withdrawal;

    public int $cookie_days;

    public static function group(): string
    {
        return 'affiliate';
    }
}
