<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Menus;

use App\Enums\MenuLocation;
use App\Filament\Admin\Resources\Menus\Pages\CreateMenu;
use App\Filament\Admin\Resources\Menus\Pages\EditMenu;
use App\Filament\Admin\Resources\Menus\Pages\ListMenus;
use App\Filament\Admin\Resources\Menus\RelationManagers\ItemsRelationManager;
use App\Filament\Support\TranslateAction;
use App\Models\Menu;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The storefront's navigation.
 *
 * A menu is bound to a location rather than being picked per page, because the template has
 * exactly six slots and a menu that belongs to none of them would render nowhere.
 */
final class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|null|UnitEnum $navigationGroup = 'Content';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(80)
                        ->helperText('For your reference in this list. Footer menus also show their column title to customers.'),

                    Select::make('location')
                        ->options(MenuLocation::class)
                        ->required()
                        ->native(false)
                        ->helperText('Where the menu appears. Each location shows one menu.'),

                    TextInput::make('column_title')
                        ->label('Column heading')
                        ->maxLength(80)
                        ->helperText('Shown above the links in the footer. Leave empty to use the name.'),

                    Toggle::make('is_active')->label('Active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('location')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('location')->badge()->sortable(),
                TextColumn::make('column_title')->label('Column heading')->toggleable(),
                TextColumn::make('items_count')->counts('items')->label('Links')->badge(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('location')->options(MenuLocation::class),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([ActionGroup::make([EditAction::make(), TranslateAction::make()])])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'column_title'];
    }
}
