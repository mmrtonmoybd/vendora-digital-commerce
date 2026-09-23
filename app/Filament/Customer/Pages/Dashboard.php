<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use App\Filament\Customer\Widgets\AccountStats;
use App\Filament\Customer\Widgets\RecentOrders;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class Dashboard extends BaseDashboard
{
    protected static string|null|UnitEnum $navigationGroup = 'Overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Home;

    protected static ?int $navigationSort = -2;

    public function getTitle(): string
    {
        return 'Welcome back, '.auth()->user()->name;
    }

    public function getColumns(): array
    {
        return ['md' => 2, 'xl' => 4];
    }

    public function getWidgets(): array
    {
        return [
            AccountStats::class,
            RecentOrders::class,
        ];
    }
}
