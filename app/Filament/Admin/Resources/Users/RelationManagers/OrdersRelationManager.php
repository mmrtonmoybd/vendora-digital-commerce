<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Support\MoneyColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->copyable(),
                TextColumn::make('items_summary')->label('Items')->state(fn ($record): string => $record->itemsSummary())->wrap()->limit(50),
                MoneyColumn::make('total'),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn ($record): string => \App\Filament\Admin\Resources\Orders\OrderResource::getUrl('view', ['record' => $record]));
    }
}
