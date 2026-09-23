<?php

declare(strict_types=1);

namespace App\Filament\Customer\Widgets;

use App\Enums\AccessStatus;
use App\Enums\CommissionStatus;
use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReferralCommission;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/** The customer's own numbers. Every query is scoped to them; nothing global leaks in. */
final class AccountStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function getColumns(): array
    {
        return ['default' => 1, 'sm' => 2, 'xl' => 4];
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        $data = Cache::remember("customer:{$userId}:stats", now()->addSeconds(60), function () use ($userId): array {
            $orders = fn (array $statuses): int => Order::where('user_id', $userId)
                ->whereIn('status', $statuses)->count();

            $deposits = fn (DepositStatus $status): int => (int) Deposit::where('user_id', $userId)
                ->where('status', $status->value)->sum('amount');

            return [
                'completed' => $orders([OrderStatus::Completed->value]),
                'pending' => $orders([
                    OrderStatus::PendingPayment->value,
                    OrderStatus::Pending->value,
                    OrderStatus::Processing->value,
                ]),
                'refunded' => $orders([OrderStatus::Refunded->value]),
                'deposit_completed' => $deposits(DepositStatus::Completed),
                'deposit_pending' => $deposits(DepositStatus::Pending),
                'deposit_rejected' => $deposits(DepositStatus::Rejected),
                'referral_bonus' => (int) ReferralCommission::where('referrer_id', $userId)
                    ->where('status', CommissionStatus::Approved->value)->sum('amount'),
                'awaiting_access' => OrderItem::query()
                    ->whereIn('access_status', [AccessStatus::Awaiting->value, AccessStatus::InProgress->value])
                    ->whereHas('order', fn ($q) => $q->where('user_id', $userId))
                    ->count(),
            ];
        });

        return [
            Stat::make('Completed orders', (string) $data['completed'])->icon(Heroicon::CheckCircle)->color('success'),
            Stat::make('Pending orders', (string) $data['pending'])->icon(Heroicon::Clock)->color($data['pending'] > 0 ? 'warning' : 'gray'),
            Stat::make('Awaiting access', (string) $data['awaiting_access'])
                ->description('We are working on these now')
                ->icon(Heroicon::Key)
                ->color($data['awaiting_access'] > 0 ? 'warning' : 'success'),
            Stat::make('Refunded orders', (string) $data['refunded'])->icon(Heroicon::ArrowUturnLeft)->color('gray'),
            Stat::make('Deposits completed', Money::format($data['deposit_completed']))->icon(Heroicon::ArrowDownTray)->color('success'),
            Stat::make('Deposits pending', Money::format($data['deposit_pending']))->icon(Heroicon::Clock)->color($data['deposit_pending'] > 0 ? 'warning' : 'gray'),
            Stat::make('Deposits rejected', Money::format($data['deposit_rejected']))->icon(Heroicon::XCircle)->color($data['deposit_rejected'] > 0 ? 'danger' : 'gray'),
            Stat::make('Referral earnings', Money::format($data['referral_bonus']))->icon(Heroicon::Gift)->color('primary'),
        ];
    }
}
