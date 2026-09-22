<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class Dashboard extends BaseDashboard
{
    protected static string|null|UnitEnum $navigationGroup = 'Overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Home;

    protected static ?int $navigationSort = -2;

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 4];
    }
}
