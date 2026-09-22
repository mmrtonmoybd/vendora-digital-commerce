<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Support\MoneyColumn;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->with(['user', 'paymentMethod'])->latest()->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('order_number')->label('Order')->weight('semibold')->copyable(),
                TextColumn::make('delivery_email')->label('Access email')->copyable(),
                MoneyColumn::make('total'),
                TextColumn::make('payment_status')->label('Payment')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Placed')->since(),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]));
    }
}
