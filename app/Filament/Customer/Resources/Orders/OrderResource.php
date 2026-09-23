<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Customer\Concerns\BelongsToCustomer;
use App\Filament\Customer\Resources\Orders\Pages\ListOrders;
use App\Filament\Customer\Resources\Orders\Pages\ViewOrder;
use App\Filament\Customer\Resources\Orders\Schemas\OrderInfolist;
use App\Models\Order;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class OrderResource extends Resource
{
    use BelongsToCustomer;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|null|UnitEnum $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'My orders';

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('items_summary')
                    ->label('Items')
                    ->state(fn (Order $record): string => $record->itemsSummary())
                    ->wrap()
                    ->limit(60),
                TextColumn::make('total')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd()->sortable(),
                TextColumn::make('payment_status')->label('Payment')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('access')
                    ->label('Access')
                    ->badge()
                    // The status the customer actually cares about: has the invitation gone out.
                    ->state(fn (Order $record): string => $record->items->first()?->access_status->label() ?? '—')
                    ->color(fn (Order $record): string => $record->items->first()?->access_status->color() ?? 'gray'),
                TextColumn::make('refunded_amount')
                    ->label('Refunded')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? Money::format($state) : '—')
                    ->toggleable(),
                TextColumn::make('created_at')->label('Placed')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class)->multiple(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('pay')
                        ->label('Pay now')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->visible(fn (Order $record): bool => ! $record->isPaid())
                        ->url(fn (Order $record): string => route('order.pay', $record), shouldOpenInNewTab: true),
                    Action::make('invoice')
                        ->label(__('invoice.download_pdf'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn (Order $record): bool => $record->isPaid())
                        ->url(fn (Order $record): string => route('order.invoice', $record), shouldOpenInNewTab: true),
                    Action::make('invoicePrint')
                        ->label(__('invoice.print'))
                        ->icon('heroicon-o-printer')
                        ->visible(fn (Order $record): bool => $record->isPaid())
                        ->url(fn (Order $record): string => route('order.invoice.print', $record), shouldOpenInNewTab: true),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
