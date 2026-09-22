<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\WalletTxType;
use App\Models\WalletTransaction;
use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The ledger for one customer.
 *
 * Read-only by construction: the model itself refuses updates and deletes, so there is no
 * edit action to offer. A mistake is corrected with a compensating entry.
 */
final class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';

    protected static ?string $title = 'Wallet ledger';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').Money::format($state))
                    ->color(fn (WalletTransaction $record): string => $record->isCredit() ? 'success' : 'danger')
                    ->weight('semibold')
                    ->alignEnd(),
                TextColumn::make('balance_after')
                    ->label('Balance after')
                    ->formatStateUsing(fn (int $state): string => Money::format($state))
                    ->alignEnd(),
                TextColumn::make('description')->wrap()->placeholder('—'),
                TextColumn::make('admin.name')->label('By')->placeholder('System')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(WalletTxType::class)->multiple(),
            ]);
    }
}
