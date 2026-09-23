<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\AccessStatus;
use App\Enums\DepositStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The numbers an owner opens the panel to see.
 *
 * Cached for a minute: these run several aggregates, and a dashboard that re-queries on
 * every poll costs more than the freshness is worth.
 */
final class BusinessStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function getColumns(): array
    {
        return ['default' => 1, 'sm' => 2, 'xl' => 4];
    }

    protected function getStats(): array
    {
        $data = Cache::remember('dashboard:business-stats', now()->addSeconds(60), function (): array {
            $paid = [OrderPaymentStatus::Paid->value, OrderPaymentStatus::PartiallyRefunded->value];

            $revenue = fn (\DateTimeInterface $from, ?\DateTimeInterface $to = null): int => (int) Order::query()
                ->whereIn('payment_status', $paid)
                ->where('paid_at', '>=', $from)
                ->when($to !== null, fn ($q) => $q->where('paid_at', '<', $to))
                ->sum(DB::raw('total - refunded_amount'));

            return [
                'today' => $revenue(today()),
                'yesterday' => $revenue(today()->subDay(), today()),
                'week' => $revenue(today()->subDays(7)),
                'previous_week' => $revenue(today()->subDays(14), today()->subDays(7)),
                'month' => $revenue(today()->subDays(30)),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'open_orders' => Order::query()->whereIn('status', [
                    OrderStatus::PendingPayment->value,
                    OrderStatus::Pending->value,
                    OrderStatus::Processing->value,
                ])->count(),
                'awaiting_verification' => Order::where('payment_status', OrderPaymentStatus::Submitted->value)->count(),
                'awaiting_access' => OrderItem::whereIn('access_status', [
                    AccessStatus::Awaiting->value,
                    AccessStatus::InProgress->value,
                ])->count(),
                'pending_deposits' => Deposit::where('status', DepositStatus::Pending->value)->count(),
                'pending_deposit_value' => (int) Deposit::where('status', DepositStatus::Pending->value)->sum('amount'),
                'open_tickets' => Ticket::query()->whereIn('status', [
                    TicketStatus::Open->value,
                    TicketStatus::CustomerReply->value,
                ])->count(),
                'liability' => (int) User::sum('balance'),
                'new_customers' => User::whereDate('created_at', today())->count(),
            ];
        });

        return [
            Stat::make('Revenue today', Money::format($data['today']))
                ->description($this->trend($data['today'], $data['yesterday']).' vs yesterday')
                ->descriptionIcon($data['today'] >= $data['yesterday'] ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color($data['today'] >= $data['yesterday'] ? 'success' : 'danger')
                ->icon(Heroicon::Banknotes),

            Stat::make('Revenue last 7 days', Money::format($data['week']))
                ->description($this->trend($data['week'], $data['previous_week']).' vs previous 7')
                ->color($data['week'] >= $data['previous_week'] ? 'success' : 'warning')
                ->icon(Heroicon::ChartBar),

            Stat::make('Revenue last 30 days', Money::format($data['month']))
                ->description($data['orders_today'].' orders placed today')
                ->color('primary')
                ->icon(Heroicon::CalendarDays),

            Stat::make('Wallet liability', Money::format($data['liability']))
                ->description('Money owed to customers')
                ->color('warning')
                ->icon(Heroicon::Wallet),

            Stat::make('Awaiting access', (string) $data['awaiting_access'])
                ->description('Items waiting to be granted')
                ->color($data['awaiting_access'] > 0 ? 'danger' : 'success')
                ->icon(Heroicon::Key),

            Stat::make('Payments to verify', (string) $data['awaiting_verification'])
                ->description('Manual receipts submitted')
                ->color($data['awaiting_verification'] > 0 ? 'warning' : 'gray')
                ->icon(Heroicon::DocumentCheck),

            Stat::make('Pending deposits', (string) $data['pending_deposits'])
                ->description(Money::format($data['pending_deposit_value']).' waiting')
                ->color($data['pending_deposits'] > 0 ? 'warning' : 'gray')
                ->icon(Heroicon::ArrowDownTray),

            Stat::make('Open tickets', (string) $data['open_tickets'])
                ->description($data['new_customers'].' new customers today')
                ->color($data['open_tickets'] > 0 ? 'danger' : 'success')
                ->icon(Heroicon::Lifebuoy),
        ];
    }

    /** A percentage change that reads sensibly when the baseline was zero. */
    private function trend(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? 'New' : 'No change';
        }

        $change = ($current - $previous) / $previous * 100;

        return sprintf('%+.0f%%', $change);
    }
}
