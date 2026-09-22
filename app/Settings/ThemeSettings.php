<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class ThemeSettings extends Settings
{
    /** One hex colour. The 50–900 RGB triplets the template needs are derived from it. */
    public string $brand_hex;

    public string $admin_primary_hex;

    public int $hero_autoplay_seconds;

    public int $home_products_per_section;

    public static function group(): string
    {
        return 'theme';
    }
}
