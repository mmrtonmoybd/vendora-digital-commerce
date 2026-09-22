<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\CommerceSettings;

/**
 * Every amount in this system is an integer number of paisa. ৳1,450.00 is 145000.
 *
 * Floats are never used for money: 0.1 + 0.2 is not 0.3, and a wallet that drifts by a
 * paisa per thousand transactions is a wallet nobody can reconcile.
 */
final class Money
{
    /**
     * Format paisa for display.
     *
     * The template prints whole-taka prices without decimals ("৳ 2,990"), so the fraction is
     * only shown when there actually is one.
     */
    public static function format(int $paisa, ?string $symbol = null, bool $forceDecimals = false): string
    {
        $symbol ??= self::symbol();
        $negative = $paisa < 0;
        $abs = abs($paisa);
        $taka = intdiv($abs, 100);
        $fraction = $abs % 100;

        $number = $forceDecimals || $fraction !== 0
            ? number_format($taka).'.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT)
            : number_format($taka);

        return ($negative ? '-' : '').$symbol.' '.$number;
    }

    /** Format without the thin space the template uses after the symbol. */
    public static function formatTight(int $paisa, ?string $symbol = null): string
    {
        return str_replace(' ', '', self::format($paisa, $symbol));
    }

    /** Parse a human-typed amount ("1,450.50") into paisa. */
    public static function fromDecimal(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value) ?? '0';

        if ($clean === '' || $clean === '-') {
            return 0;
        }

        return (int) round(((float) $clean) * 100);
    }

    /** Paisa back to a decimal string, for exports and API payloads. */
    public static function toDecimal(int $paisa): string
    {
        return number_format($paisa / 100, 2, '.', '');
    }

    /**
     * A percentage expressed in basis points (1% = 100 bps), rounded half up.
     *
     * Fees and commissions are stored in bps so the rate itself never needs a float column.
     */
    public static function percentOfBps(int $paisa, int $bps): int
    {
        return (int) round($paisa * $bps / 10000);
    }

    /** A whole-percent share (coupons store 1–100), rounded half up. */
    public static function percent(int $paisa, int $percent): int
    {
        return (int) round($paisa * $percent / 100);
    }

    public static function symbol(): string
    {
        try {
            return app(CommerceSettings::class)->currency_symbol;
        } catch (\Throwable) {
            return '৳';
        }
    }

    public static function currencyCode(): string
    {
        try {
            return app(CommerceSettings::class)->currency_code;
        } catch (\Throwable) {
            return 'BDT';
        }
    }
}
