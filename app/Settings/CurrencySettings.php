<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class CurrencySettings extends Settings
{
    /** @var array<int, string> */
    public array $enabled_currencies;

    public string $default_currency;

    /**
     * How many paisa make one unit of each currency.
     *
     * Integers, because every amount in this system is an integer and a rate stored as a
     * float would be the one place a rounding error could enter.
     *
     * @var array<string, int>
     */
    public array $rates;

    public bool $show_switcher;

    /** Warn the customer at checkout that the charge is taken in taka. */
    public bool $show_settlement_note;

    public static function group(): string
    {
        return 'currency';
    }
}
