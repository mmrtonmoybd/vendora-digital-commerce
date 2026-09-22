<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\OrderPaymentStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Revenue and orders';

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    protected ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            '365' => 'Last 12 months',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $monthly = $days > 90;

        $rows = Cache::remember("dashboard:revenue:{$days}", now()->addSeconds(60), function () use ($days, $monthly): array {
            // One grouped query rather than a query per bucket: a year of daily loops is
            // 365 round trips for a chart nobody waits that long for.
            $format = $monthly ? '%Y-%m' : '%Y-%m-%d';

            return Order::query()
                ->whereIn('payment_status', [OrderPaymentStatus::Paid->value, OrderPaymentStatus::PartiallyRefunded->value])
                ->where('paid_at', '>=', now()->subDays($days)->startOfDay())
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get([
                    DB::raw("DATE_FORMAT(paid_at, '{$format}') as bucket"),
                    DB::raw('SUM(total - refunded_amount) as revenue'),
                    DB::raw('COUNT(*) as orders'),
                ])
                ->keyBy('bucket')
                ->map(fn ($row): array => [
                    'revenue' => (int) $row->revenue,
                    'orders' => (int) $row->orders,
                ])
                ->all();
        });

        $labels = [];
        $revenue = [];
        $orders = [];

        // Walk the whole period so empty days render as zero instead of collapsing the axis.
        $cursor = $monthly ? now()->subDays($days)->startOfMonth() : now()->subDays($days)->startOfDay();

        while ($cursor->lte(now())) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->format('Y-m-d');

            $labels[] = $monthly ? $cursor->format('M Y') : $cursor->format('j M');
            $revenue[] = round(($rows[$key]['revenue'] ?? 0) / 100, 2);
            $orders[] = $rows[$key]['orders'] ?? 0;

            $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenue,
                    'borderColor' => '#dc2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Orders',
                    'data' => $orders,
                    'borderColor' => '#0d9488',
                    'backgroundColor' => 'rgba(13, 148, 136, 0.12)',
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['type' => 'linear', 'position' => 'left', 'title' => ['display' => true, 'text' => 'Revenue']],
                'y1' => ['type' => 'linear', 'position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => 'Orders']],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
