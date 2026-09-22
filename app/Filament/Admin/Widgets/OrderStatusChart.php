<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

final class OrderStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Orders by status';

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 1];

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $counts = Cache::remember(
            'dashboard:order-status',
            now()->addSeconds(60),
            fn (): array => Order::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
        );

        $labels = [];
        $data = [];
        $colors = [];

        $palette = [
            OrderStatus::PendingPayment->value => '#ef4444',
            OrderStatus::Pending->value => '#f59e0b',
            OrderStatus::Processing->value => '#0ea5e9',
            OrderStatus::Completed->value => '#10b981',
            OrderStatus::Cancelled->value => '#78716c',
            OrderStatus::Refunded->value => '#a8a29e',
        ];

        foreach (OrderStatus::cases() as $status) {
            if (($counts[$status->value] ?? 0) === 0) {
                continue;
            }

            $labels[] = $status->label();
            $data[] = $counts[$status->value];
            $colors[] = $palette[$status->value];
        }

        return [
            'datasets' => [['data' => $data, 'backgroundColor' => $colors]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
