<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Enums\StockLogReason;
use App\Filament\Support\MoneyColumn;
use App\Models\Product;
use App\Services\StockService;
use App\Settings\CommerceSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\TrashedFilter;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold')->wrap()
                    ->description(fn (Product $record): ?string => $record->short),
                TextColumn::make('category.name')->label('Category')->searchable()->sortable()->badge()->color('gray'),
                MoneyColumn::make('price'),
                TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $record): string => match (true) {
                        $record->stock < 1 => 'danger',
                        $record->stock <= app(CommerceSettings::class)->low_stock_threshold => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('total_sold')->label('Sold')->sortable()->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('platform.name')->label('Platform')->toggleable()->badge()->color('info'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name')->searchable()->preload()->multiple(),
                SelectFilter::make('platform')->relationship('platform', 'name')->searchable()->preload()->multiple(),
                TernaryFilter::make('is_active')->label('Active'),
                Filter::make('low_stock')
                    ->label('Low stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', app(CommerceSettings::class)->low_stock_threshold)),
                Filter::make('out_of_stock')
                    ->label('Out of stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0)),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Created from')->native(false),
                        DatePicker::make('until')->label('Created until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Created from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Created until '.$data['until'] : null,
                    ]))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('adjustStock')
                        ->label('Adjust stock')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            TextInput::make('stock')
                                ->label('New stock level')
                                ->numeric()->required()->minValue(0)
                                ->default(fn (Product $record): int => $record->stock),
                            TextInput::make('note')->label('Reason')->required()->maxLength(190),
                        ])
                        // Goes through StockService so the change is written to the stock log
                        // with who made it and why, like every other movement.
                        ->action(function (Product $record, array $data): void {
                            app(StockService::class)->setTo($record, (int) $data['stock'], auth('admin')->user(), $data['note']);

                            Notification::make()->success()->title('Stock updated')->send();
                        }),
                    ReplicateAction::make()
                        ->excludeAttributes(['slug', 'total_sold', 'views', 'created_at', 'updated_at'])
                        ->beforeReplicaSaved(function (Product $replica): void {
                            $replica->name = $replica->name.' (copy)';
                            $replica->slug = null;
                            $replica->is_active = false;
                        }),
                    Action::make('view')
                        ->label('View on site')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Product $record): string => route('product.show', $record), shouldOpenInNewTab: true),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('moveCategory')
                        ->label('Move to category')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Select::make('category_id')->label('Category')->relationship('category', 'name')->searchable()->preload()->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['category_id' => $data['category_id']]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('adjustPrice')
                        ->label('Adjust price by %')
                        ->icon('heroicon-o-receipt-percent')
                        ->schema([
                            TextInput::make('percent')
                                ->numeric()->required()
                                ->helperText('Use a negative number to discount, e.g. -10 for 10% off.'),
                        ])
                        ->requiresConfirmation()
                        ->action(function (Collection $records, array $data): void {
                            $percent = (int) $data['percent'];

                            $records->each(function (Product $product) use ($percent): void {
                                // Prices stay integers: the multiplier is applied to paisa
                                // and rounded once.
                                $product->update([
                                    'price' => max(0, (int) round($product->price * (100 + $percent) / 100)),
                                ]);
                            });

                            Notification::make()->success()->title('Prices updated')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
