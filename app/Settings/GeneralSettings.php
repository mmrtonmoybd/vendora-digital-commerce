<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class GeneralSettings extends Settings
{
    public string $site_name;

    public string $tagline;

    public ?string $logo;

    public ?string $logo_footer;

    public ?string $favicon;

    public string $copyright_text;

    public string $support_hours;

    public static function group(): string
    {
        return 'general';
    }
}
