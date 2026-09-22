<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\WalletTransactions;

use App\Enums\WalletTxType;
use App\Filament\Customer\Concerns\BelongsToCustomer;
use App\Filament\Customer\Resources\WalletTransactions\Pages\ListWalletTransactions;
use App\Models\WalletTransaction;
use App\Support\Money;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** The customer's own statement, with the running balance the ledger already records. */
final class WalletTransactionResource extends Resource
{
    use BelongsToCustomer;

    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|null|UnitEnum $navigationGroup = 'Wallet';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Statement';

    protected static ?string $modelLabel = 'transaction';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([25, 50, 100])
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('description')->wrap()->placeholder('—'),
                TextColumn::make('amount')
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').Money::format($s))
                    ->color(fn (WalletTransaction $r): string => $r->isCredit() ? 'success' : 'danger')
                    ->weight('semibold')
                    ->alignEnd(),
                TextColumn::make('balance_after')
                    ->label('Balance')
                    ->formatStateUsing(fn (int $state): string => Money::format($state))
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('type')->options(WalletTxType::class)->multiple(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('From')->native(false),
                        DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'From '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Until '.$data['until'] : null,
                    ]))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListWalletTransactions::route('/')];
    }
}
