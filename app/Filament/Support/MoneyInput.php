<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\Money;
use Filament\Forms\Components\TextInput;

/**
 * A money field that shows taka but stores paisa.
 *
 * Operators think in taka; the database only ever holds integers. Doing the conversion in
 * one place means no form can accidentally write a float into a money column.
 */
final class MoneyInput
{
    public static function make(string $name, ?string $label = null): TextInput
    {
        return TextInput::make($name)
            ->label($label ?? str($name)->headline()->value())
            ->numeric()
            ->minValue(0)
            ->step('0.01')
            ->prefix(Money::symbol())
            ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : Money::toDecimal($state))
            ->dehydrateStateUsing(fn (?string $state): ?int => $state === null || $state === '' ? null : Money::fromDecimal($state));
    }
}
