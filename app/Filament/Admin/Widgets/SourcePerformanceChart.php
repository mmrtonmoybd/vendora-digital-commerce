<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\OrderPaymentStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Which platform's traffic actually turns into money.
 *
 * This is the question the attribution middleware exists to answer, so it gets a chart on
 * the dashboard rather than being buried in a report.
 */
final class SourcePerformanceChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Revenue by source';

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    protected ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;

        $rows = Cache::remember("dashboard:sources:{$days}", now()->addSeconds(60), fn (): array => Order::query()
            ->leftJoin('order_sources', 'orders.order_source_id', '=', 'order_sources.id')
            ->whereIn('orders.payment_status', [OrderPaymentStatus::Paid->value, OrderPaymentStatus::PartiallyRefunded->value])
            ->where('orders.paid_at', '>=', now()->subDays($days))
            ->groupBy('source')
            ->orderByDesc('revenue')
            ->get([
                DB::raw("COALESCE(order_sources.name, 'Direct') as source"),
                DB::raw('SUM(orders.total - orders.refunded_amount) as revenue'),
            ])
            ->pluck('revenue', 'source')
            ->all());

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => array_map(fn ($paisa): float => round((int) $paisa / 100, 2), array_values($rows)),
                'backgroundColor' => '#dc2626',
            ]],
            'labels' => array_keys($rows),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
