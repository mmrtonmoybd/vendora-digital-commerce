<?php

declare(strict_types=1);

namespace App\Filament\Customer\Widgets;

use App\Filament\Customer\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\Money;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class RecentOrders extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Your latest orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()
                ->where('user_id', auth()->id())
                ->with('items')
                ->latest()
                ->limit(5))
            ->paginated(false)
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('When you buy something it will show up here.')
            ->columns([
                TextColumn::make('order_number')->label('Order')->weight('semibold'),
                TextColumn::make('items_summary')->label('Items')->state(fn (Order $r): string => $r->itemsSummary())->wrap()->limit(40),
                TextColumn::make('total')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Placed')->since(),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]));
    }
}
