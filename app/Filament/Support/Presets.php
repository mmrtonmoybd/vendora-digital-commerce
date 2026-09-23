<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * The closed option lists an administrator picks presentation values from.
 *
 * Nothing typed into a form ever becomes a CSS class: every gradient, chip colour and icon
 * comes from config/storefront.php, which is also what the Tailwind safelist is built from.
 */
final class Presets
{
    /** @return array<string, string> */
    public static function gradients(): array
    {
        return (array) config('storefront.gradients');
    }

    /** @return array<string, string> */
    public static function heroGradients(): array
    {
        return collect((array) config('storefront.hero_gradients'))
            ->map(fn (array $preset): string => $preset['label'])
            ->all();
    }

    /** @return array<string, string> */
    public static function icons(): array
    {
        return collect((array) config('storefront.icons'))
            ->map(fn (array $icon): string => $icon['label'])
            ->all();
    }

    /** @return array<string, string> */
    public static function badgeStyles(): array
    {
        return collect((array) config('storefront.badge_styles'))
            ->map(fn (array $style): string => $style['label'])
            ->all();
    }

    /** @return array<string, string> */
    public static function chipColors(): array
    {
        return config('storefront.chip_colors');
    }

    /** @return array<string, string> */
    public static function chipClasses(): array
    {
        return config('storefront.chip_classes');
    }

    /** @return array<string, string> */
    public static function avatarColors(): array
    {
        return config('storefront.avatar_colors');
    }
}
