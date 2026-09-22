<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Widgets;

use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Widgets\Concerns\SummarisesTable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;

final class CustomerStats extends StatsOverviewWidget
{
    use InteractsWithPageTable, SummarisesTable;

    protected int|string|array $columnSpan = 'full';

    public function getColumns(): int|array
    {
        return ['default' => 1, 'sm' => 2, 'xl' => 4];
    }

    protected function getTablePage(): string
    {
        return ListUsers::class;
    }

    protected function getStats(): array
    {
        $totals = $this->summarise([
            'customers' => 'COUNT(*)',
            'active' => "SUM(CASE WHEN status = '".UserStatus::Active->value."' THEN 1 ELSE 0 END)",
            'liability' => 'COALESCE(SUM(balance), 0)',
            'verified' => 'SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END)',
        ]);

        return [
            $this->summaryStat('Customers in view', $this->quantity($totals['customers']), Heroicon::Users, 'gray', "{$totals['active']} active"),
            $this->summaryStat('Wallet liability', $this->money($totals['liability']), Heroicon::Wallet, 'warning', 'Money we owe these customers'),
            $this->summaryStat('Email verified', $this->quantity($totals['verified']), Heroicon::CheckBadge, 'success'),
            $this->summaryStat('Unverified', $this->quantity((int) $totals['customers'] - (int) $totals['verified']), Heroicon::ExclamationTriangle, 'danger'),
        ];
    }
}
