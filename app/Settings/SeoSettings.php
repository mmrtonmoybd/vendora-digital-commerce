<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class SeoSettings extends Settings
{
    public string $default_title;

    public string $default_description;

    public ?string $og_image;

    public string $ga4_id;

    public string $meta_pixel_id;

    public string $custom_head_html;

    public string $custom_body_html;

    public string $robots_txt;

    public static function group(): string
    {
        return 'seo';
    }
}
