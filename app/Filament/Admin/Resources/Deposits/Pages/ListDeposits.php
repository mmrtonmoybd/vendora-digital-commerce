<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Deposits\Pages;

use App\Enums\DepositStatus;
use App\Filament\Admin\Resources\Deposits\DepositResource;
use App\Models\Deposit;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    /**
     * "All" is deliberately first, and therefore the default.
     *
     * Filament resolves the default tab while the table query is still being assembled, so
     * a default tab that carries its own query modifier renders without a model context.
     * Opening a queue on everything and letting the operator narrow is the better default
     * anyway.
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')->badge(Deposit::count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', DepositStatus::Pending->value))
                ->badge(Deposit::where('status', DepositStatus::Pending->value)->count())
                ->badgeColor('warning'),
            'awaiting_payment' => Tab::make('Awaiting payment')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', DepositStatus::AwaitingPayment->value)),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', DepositStatus::Completed->value)),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', DepositStatus::Rejected->value)),
        ];
    }
}
