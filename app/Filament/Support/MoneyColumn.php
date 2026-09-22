<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\Money;
use Filament\Tables\Columns\TextColumn;

final class MoneyColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? str($name)->headline()->value())
            ->formatStateUsing(fn (?int $state): string => Money::format((int) $state))
            ->alignEnd()
            ->sortable();
    }
}
