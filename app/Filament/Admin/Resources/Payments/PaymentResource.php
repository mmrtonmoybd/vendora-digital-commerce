<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Payments\Pages\ListPayments;
use App\Filament\Support\MoneyColumn;
use App\Models\Payment;
use App\Services\ShurjopayService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Gateway attempts, read-only. The record of what the gateway actually said. */
final class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|null|UnitEnum $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Gateway payments';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('merchant_order_id')->label('Reference')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('sp_order_id')->label('Gateway ID')->searchable()->copyable()->placeholder('—'),
                TextColumn::make('payable_type')
                    ->label('For')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (Payment $record): string => str($record->payableKind())->headline()),
                MoneyColumn::make('amount'),
                TextColumn::make('status')->badge(),
                TextColumn::make('method')->label('Paid via')->placeholder('—')->toggleable(),
                TextColumn::make('sp_message')->label('Gateway said')->wrap()->limit(50)->placeholder('—')->toggleable(),
                TextColumn::make('attempts')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Started')->dateTime()->sortable(),
                TextColumn::make('verified_at')->label('Verified')->dateTime()->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PaymentStatus::class)->multiple(),
                Filter::make('unresolved')
                    ->label('Still open')
                    ->query(fn (Builder $query): Builder => $query->open()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Started from')->native(false),
                        DatePicker::make('until')->label('Started until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Started from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Started until '.$data['until'] : null,
                    ]))),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('verifyNow')
                        ->label('Ask the gateway again')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (Payment $record): bool => filled($record->sp_order_id) && ! $record->isPaid())
                        ->action(function (Payment $record): void {
                            $result = app(ShurjopayService::class)->verifyAndApply((string) $record->sp_order_id, 'admin');

                            $result?->isPaid()
                                ? Notification::make()->success()->title('The gateway confirms this is paid')->send()
                                : Notification::make()->warning()
                                    ->title('No confirmed payment')
                                    ->body($result?->sp_message ?? 'The gateway had nothing to add.')
                                    ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['merchant_order_id', 'sp_order_id', 'bank_trx_id'];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
