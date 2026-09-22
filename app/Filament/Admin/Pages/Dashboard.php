<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\BusinessStats;
use App\Filament\Admin\Widgets\LatestOrders;
use App\Filament\Admin\Widgets\LowStockProducts;
use App\Filament\Admin\Widgets\OrderStatusChart;
use App\Filament\Admin\Widgets\RevenueChart;
use App\Filament\Admin\Widgets\SourcePerformanceChart;
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
        return ['md' => 2, 'xl' => 3];
    }

    /**
     * Listed explicitly rather than auto-discovered, so the order the operator reads them
     * in is a decision rather than an accident of file names.
     */
    public function getWidgets(): array
    {
        return [
            BusinessStats::class,
            RevenueChart::class,
            OrderStatusChart::class,
            SourcePerformanceChart::class,
            LatestOrders::class,
            LowStockProducts::class,
        ];
    }
}
