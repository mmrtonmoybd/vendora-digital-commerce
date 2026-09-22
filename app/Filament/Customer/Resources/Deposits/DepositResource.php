<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Deposits;

use App\Enums\DepositStatus;
use App\Filament\Customer\Concerns\BelongsToCustomer;
use App\Filament\Customer\Resources\Deposits\Pages\CreateDeposit;
use App\Filament\Customer\Resources\Deposits\Pages\ListDeposits;
use App\Models\Deposit;
use App\Support\Money;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class DepositResource extends Resource
{
    use BelongsToCustomer;

    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|null|UnitEnum $navigationGroup = 'Wallet';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Deposits';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('deposit_number')->label('Reference')->copyable()->weight('semibold'),
                TextColumn::make('paymentMethod.name')->label('Method')->badge()->color('gray'),
                TextColumn::make('amount')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('transaction_id')->label('TrxID')->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('reject_reason')
                    ->label('Why')
                    ->placeholder('—')
                    ->wrap()
                    // Only meaningful when something went wrong, and that is exactly when
                    // the customer needs to see it without asking.
                    ->visible(fn (): bool => true),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(DepositStatus::class)->multiple()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeposits::route('/'),
            'create' => CreateDeposit::route('/create'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }
}
