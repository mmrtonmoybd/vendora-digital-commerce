<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Admin\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|null|UnitEnum $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    /** Orders that still need someone to do something. */
    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->whereIn('status', [
            OrderStatus::PendingPayment->value,
            OrderStatus::Pending->value,
            OrderStatus::Processing->value,
        ])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'delivery_email', 'guest_email', 'manual_transaction_id'];
    }

    public static function canCreate(): bool
    {
        // Orders are placed by customers, or raised from a custom invoice. Creating one by
        // hand would skip stock reservation and payment entirely.
        return false;
    }
}
