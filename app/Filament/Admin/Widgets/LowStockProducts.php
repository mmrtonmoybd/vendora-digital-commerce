<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Support\MoneyColumn;
use App\Models\Product;
use App\Settings\CommerceSettings;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Products about to sell out, so someone can restock before the shop says "Out of Stock". */
final class LowStockProducts extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Low stock';

    public static function canView(): bool
    {
        return Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', app(CommerceSettings::class)->low_stock_threshold)
            ->exists();
    }

    public function table(Table $table): Table
    {
        $threshold = app(CommerceSettings::class)->low_stock_threshold;

        return $table
            ->query(fn (): Builder => Product::query()
                ->with('category')
                ->where('is_active', true)
                ->where('stock', '<=', $threshold)
                ->orderBy('stock'))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->weight('semibold')->wrap(),
                TextColumn::make('category.name')->label('Category')->badge()->color('gray'),
                TextColumn::make('stock')
                    ->badge()
                    ->color(fn (Product $record): string => $record->stock < 1 ? 'danger' : 'warning'),
                TextColumn::make('total_sold')->label('Sold'),
                MoneyColumn::make('price'),
            ])
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]));
    }
}
