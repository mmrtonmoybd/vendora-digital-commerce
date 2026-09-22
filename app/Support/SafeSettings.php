<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\GeneralSettings;
use App\Settings\ThemeSettings;

/**
 * Settings reads that survive the settings table not existing yet.
 *
 * Both panels are built during boot, and boot happens before `migrate` has created
 * anything — on a fresh clone, in CI, and in every test that refreshes the database. A
 * panel that throws in that window makes the very command you need to run impossible.
 */
final class SafeSettings
{
    public const FALLBACK_BRAND_HEX = '#dc2626';

    public static function brandHex(): string
    {
        return self::read(fn (): string => app(ThemeSettings::class)->brand_hex, self::FALLBACK_BRAND_HEX);
    }

    public static function adminPrimaryHex(): string
    {
        return self::read(fn (): string => app(ThemeSettings::class)->admin_primary_hex, self::FALLBACK_BRAND_HEX);
    }

    public static function siteName(): string
    {
        return self::read(fn (): string => app(GeneralSettings::class)->site_name, (string) config('app.name'));
    }

    public static function favicon(): ?string
    {
        return self::read(fn (): ?string => app(GeneralSettings::class)->favicon, null);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $read
     * @param  T  $fallback
     * @return T
     */
    private static function read(callable $read, mixed $fallback): mixed
    {
        try {
            return $read();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
