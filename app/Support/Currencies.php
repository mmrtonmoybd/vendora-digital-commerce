<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\CurrencySettings;

/**
 * Display currencies.
 *
 * The shop's money is Bangladeshi taka and nothing here changes that: every amount is
 * stored, calculated, charged and refunded in integer paisa, and the gateway settles in
 * BDT. A second currency is a *presentation* of that number for a visitor who does not
 * think in taka — never a second set of prices, and never what the customer is charged.
 *
 * That distinction is the whole design. Storing prices per currency would mean a rate
 * change silently repricing the catalogue, two numbers that can disagree, and a refund
 * that does not match the capture.
 *
 * Rates are held as "how many paisa make one unit of this currency", an integer, so no
 * float ever touches a conversion.
 */
final class Currencies
{
    public const BASE = 'BDT';

    /**
     * @var array<string, array{symbol: string, name: string, decimals: int}>
     */
    public const SUPPORTED = [
        'BDT' => ['symbol' => '৳', 'name' => 'Bangladeshi taka', 'decimals' => 0],
        'USD' => ['symbol' => '$', 'name' => 'US dollar', 'decimals' => 2],
    ];

    /**
     * The currency this request is being shown in.
     *
     * Request-scoped and set only by the storefront middleware, so the admin panel, the
     * invoice and every report keep reading the base currency — which is the number that
     * actually moved.
     */
    private static ?string $current = null;

    public static function setCurrent(?string $code): void
    {
        self::$current = self::isEnabled($code) ? $code : null;
    }

    public static function current(): string
    {
        return self::$current ?? self::default();
    }

    /** Render amounts in the base currency for the rest of this request, whatever the visitor chose. */
    public static function forgetCurrent(): void
    {
        self::$current = null;
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::SUPPORTED);
    }

    public static function isEnabled(?string $code): bool
    {
        return $code !== null && in_array($code, self::enabled(), true);
    }

    /**
     * The currencies a visitor may be shown.
     *
     * The base is always present: it is what the customer is charged, so it can never be
     * configured away.
     *
     * @return array<int, string>
     */
    public static function enabled(): array
    {
        $settings = self::settings();

        $enabled = array_values(array_filter(
            $settings?->enabled_currencies ?? [self::BASE],
            self::isSupported(...),
        ));

        if (! in_array(self::BASE, $enabled, true)) {
            array_unshift($enabled, self::BASE);
        }

        return $enabled;
    }

    public static function default(): string
    {
        $default = self::settings()?->default_currency;

        return self::isEnabled($default) ? $default : self::BASE;
    }

    public static function isMultiCurrency(): bool
    {
        return count(self::enabled()) > 1;
    }

    public static function showsSwitcher(): bool
    {
        return (self::settings()?->show_switcher ?? true) && self::isMultiCurrency();
    }

    public static function symbol(string $code): string
    {
        return self::SUPPORTED[$code]['symbol'] ?? $code;
    }

    public static function name(string $code): string
    {
        return self::SUPPORTED[$code]['name'] ?? $code;
    }

    public static function decimals(string $code): int
    {
        return self::SUPPORTED[$code]['decimals'] ?? 2;
    }

    /** How many paisa make one unit of this currency. One taka is 100 paisa, by definition. */
    public static function rate(string $code): int
    {
        if ($code === self::BASE) {
            return 100;
        }

        $rate = (int) (self::settings()?->rates[$code] ?? 0);

        // A missing or nonsensical rate must not divide by zero or hand out free money;
        // falling back to the base rate shows taka, which is at least true.
        return $rate > 0 ? $rate : 100;
    }

    /**
     * Convert base paisa into the smallest unit of the display currency.
     *
     * Rounded half up at the last step only, so a page of line items does not drift away
     * from its own total.
     */
    public static function convert(int $basePaisa, string $code): int
    {
        if ($code === self::BASE) {
            return $basePaisa;
        }

        $subunits = 10 ** self::decimals($code);

        return (int) round($basePaisa * $subunits / self::rate($code));
    }

    private static function settings(): ?CurrencySettings
    {
        try {
            return app(CurrencySettings::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
