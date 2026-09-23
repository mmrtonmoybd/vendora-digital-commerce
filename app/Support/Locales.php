<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\LocaleSettings;

/**
 * The languages this system knows how to speak.
 *
 * Which of them are *offered* is an administrator's decision, stored in LocaleSettings.
 * This class only answers "what exists" and "is this string a language we recognise" — it
 * never decides policy, so a locale removed from the panel stops being offered without any
 * code here changing.
 */
final class Locales
{
    /**
     * @var array<string, array{native: string, english: string, dir: string, flag: string}>
     */
    public const SUPPORTED = [
        'en' => ['native' => 'English', 'english' => 'English', 'dir' => 'ltr', 'flag' => '🇬🇧'],
        'bn' => ['native' => 'বাংলা', 'english' => 'Bengali', 'dir' => 'ltr', 'flag' => '🇧🇩'],
    ];

    /** The one locale that is always available, so a misconfigured panel cannot mute the site. */
    public const FALLBACK = 'en';

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_keys(self::SUPPORTED);
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::SUPPORTED);
    }

    /**
     * The locales a visitor may actually be served.
     *
     * The default is forced into the list: a default that is not enabled would leave the
     * resolver with nothing to fall back to.
     *
     * @return array<int, string>
     */
    public static function enabled(): array
    {
        $settings = self::settings();

        $enabled = array_values(array_filter(
            $settings?->enabled_locales ?? [self::FALLBACK],
            self::isSupported(...),
        ));

        $default = self::default();

        if (! in_array($default, $enabled, true)) {
            array_unshift($enabled, $default);
        }

        return $enabled;
    }

    public static function default(): string
    {
        $default = self::settings()?->default_locale;

        return self::isSupported($default) ? $default : self::FALLBACK;
    }

    public static function autoDetects(): bool
    {
        return self::settings()?->auto_detect ?? true;
    }

    public static function showsSwitcher(): bool
    {
        return (self::settings()?->show_switcher ?? true) && count(self::enabled()) > 1;
    }

    /** True once the site is genuinely multilingual, which is when caching has to vary. */
    public static function isMultilingual(): bool
    {
        return count(self::enabled()) > 1;
    }

    public static function isEnabled(?string $code): bool
    {
        return $code !== null && in_array($code, self::enabled(), true);
    }

    public static function nativeName(string $code): string
    {
        return self::SUPPORTED[$code]['native'] ?? $code;
    }

    public static function englishName(string $code): string
    {
        return self::SUPPORTED[$code]['english'] ?? $code;
    }

    public static function direction(string $code): string
    {
        return self::SUPPORTED[$code]['dir'] ?? 'ltr';
    }

    public static function flag(string $code): string
    {
        return self::SUPPORTED[$code]['flag'] ?? '';
    }

    /**
     * Pick the best enabled locale for a browser's Accept-Language header.
     *
     * Quality values are honoured, and a regional tag falls back to its base language so
     * "bn-BD" and "en-GB" resolve rather than being ignored.
     */
    public static function fromAcceptLanguage(?string $header): ?string
    {
        if ($header === null || trim($header) === '') {
            return null;
        }

        $enabled = self::enabled();
        $candidates = [];

        foreach (explode(',', $header) as $part) {
            $bits = explode(';', trim($part));
            $tag = strtolower(trim($bits[0]));

            if ($tag === '' || $tag === '*') {
                continue;
            }

            $quality = 1.0;

            foreach (array_slice($bits, 1) as $param) {
                if (str_starts_with(trim($param), 'q=')) {
                    $quality = (float) substr(trim($param), 2);
                }
            }

            $candidates[] = ['tag' => $tag, 'q' => $quality];
        }

        usort($candidates, fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        foreach ($candidates as $candidate) {
            $base = explode('-', $candidate['tag'])[0];

            if (in_array($base, $enabled, true)) {
                return $base;
            }
        }

        return null;
    }

    /**
     * Settings, or null before the settings table exists.
     *
     * The panel boots — and therefore reads settings — during `migrate` on a fresh install,
     * so every read here has to survive the table not being there yet.
     */
    private static function settings(): ?LocaleSettings
    {
        try {
            return app(LocaleSettings::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
