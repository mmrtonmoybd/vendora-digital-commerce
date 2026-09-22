<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Categories\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('parent.name')->label('Parent')->searchable()->toggleable(),
                TextColumn::make('products_count')->counts('products')->label('Products')->sortable()->badge(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                IconColumn::make('show_on_home')->label('Home')->boolean()->toggleable(),
                IconColumn::make('show_in_nav')->label('Nav')->boolean()->toggleable(),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('show_on_home')->label('On home page'),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()])])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
