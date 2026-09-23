<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class LocaleSettings extends Settings
{
    /** @var array<int, string> */
    public array $enabled_locales;

    public string $default_locale;

    /** Read the visitor's Accept-Language header on their first visit. */
    public bool $auto_detect;

    /** Show the language picker in the storefront header and footer. */
    public bool $show_switcher;

    /** Let the chosen language follow the customer into the account panel. */
    public bool $apply_to_customer_panel;

    /** Let it follow staff into the admin panel too. */
    public bool $apply_to_admin_panel;

    public static function group(): string
    {
        return 'locale';
    }
}
