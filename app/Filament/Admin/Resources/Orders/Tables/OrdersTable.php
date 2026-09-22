<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Orders\Support\OrderActions;
use App\Filament\Support\MoneyColumn;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->copyable(),
                TextColumn::make('delivery_email')
                    ->label('Access email')
                    ->searchable()
                    ->copyable()
                    ->description(fn (Order $record): string => $record->user?->name ?? 'Guest'),
                TextColumn::make('items_summary')
                    ->label('Items')
                    ->state(fn (Order $record): string => $record->itemsSummary())
                    ->wrap()
                    ->limit(60),
                MoneyColumn::make('total'),
                TextColumn::make('paymentMethod.name')->label('Method')->badge()->color('gray')->toggleable(),
                TextColumn::make('payment_status')->label('Payment')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('orderSource.name')
                    ->label('Source')
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->placeholder('Direct'),
                TextColumn::make('created_at')->label('Placed')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class)->multiple(),
                SelectFilter::make('payment_status')->label('Payment status')->options(OrderPaymentStatus::class)->multiple(),
                SelectFilter::make('payment_method')->relationship('paymentMethod', 'name')->preload(),
                SelectFilter::make('order_source')->label('Source')->relationship('orderSource', 'name')->preload(),
                Filter::make('needs_verification')
                    ->label('Awaiting payment verification')
                    ->query(fn (Builder $query): Builder => $query->where('payment_status', OrderPaymentStatus::Submitted->value)),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Placed from')->native(false),
                        DatePicker::make('until')->label('Placed until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Placed from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Placed until '.$data['until'] : null,
                    ]))),
                Filter::make('paid_at')
                    ->schema([
                        DatePicker::make('from')->label('Paid from')->native(false),
                        DatePicker::make('until')->label('Paid until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('paid_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('paid_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Paid from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Paid until '.$data['until'] : null,
                    ]))),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    ...OrderActions::all(),
                ]),
            ]);
    }
}
