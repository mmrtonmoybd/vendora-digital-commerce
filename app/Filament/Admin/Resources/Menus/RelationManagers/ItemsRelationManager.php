<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Menus\RelationManagers;

use App\Enums\MenuLinkType;
use App\Filament\Support\TranslateAction;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * The links inside one menu.
 *
 * A link points at a record rather than at a typed-in path wherever it can, so renaming a
 * page's slug does not quietly break the navigation. The free-text URL is still there for
 * anchors and external sites.
 *
 * `allItems` rather than `items` so second-level links are editable too — the relation used
 * by the storefront deliberately returns only the top level.
 */
final class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'allItems';

    protected static ?string $title = 'Links';

    protected static ?string $modelLabel = 'link';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('label')
                ->required()
                ->maxLength(80)
                ->columnSpanFull(),

            Select::make('link_type')
                ->label('Points to')
                ->options(MenuLinkType::class)
                ->required()
                ->live()
                ->native(false)
                ->default(MenuLinkType::Page),

            Select::make('link_target_id')
                ->label('Target')
                ->native(false)
                ->searchable()
                ->visible(fn ($get): bool => self::isRecordLink($get('link_type')))
                ->required(fn ($get): bool => self::isRecordLink($get('link_type')))
                ->options(fn ($get): array => self::targetOptions($get('link_type'))),

            TextInput::make('url')
                ->label(fn ($get): string => self::linkType($get('link_type')) === MenuLinkType::Route ? 'Route name' : 'URL')
                ->visible(fn ($get): bool => in_array(self::linkType($get('link_type')), [MenuLinkType::Url, MenuLinkType::Route], true))
                ->required(fn ($get): bool => in_array(self::linkType($get('link_type')), [MenuLinkType::Url, MenuLinkType::Route], true))
                ->maxLength(255)
                ->helperText(fn ($get): string => self::linkType($get('link_type')) === MenuLinkType::Route
                    ? 'A named route, for example "home" or "contact".'
                    : 'A full address, or an anchor such as #categories.'),

            Select::make('parent_id')
                ->label('Nested under')
                ->native(false)
                ->placeholder('Top level')
                ->options(fn (): array => $this->getOwnerRecord()
                    ->allItems()
                    ->whereNull('parent_id')
                    ->pluck('label', 'id')
                    ->all())
                // A link cannot be its own parent, and the storefront only renders two levels.
                ->disableOptionWhen(fn (string $value, ?MenuItem $record): bool => $record !== null && (int) $value === $record->getKey()),

            TextInput::make('sort_order')->numeric()->default(0),

            Toggle::make('open_in_new_tab')->label('Open in a new tab'),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('label')->searchable()->weight('semibold'),
                TextColumn::make('parent.label')->label('Nested under')->placeholder('Top level')->toggleable(),
                TextColumn::make('link_type')->label('Points to')->badge(),
                TextColumn::make('resolvedUrl')->label('Address')->limit(40)->toggleable(),
                IconColumn::make('open_in_new_tab')->label('New tab')->boolean()->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ActionGroup::make([
                EditAction::make(),
                TranslateAction::make(),
                DeleteAction::make(),
            ])])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    private static function linkType(mixed $value): ?MenuLinkType
    {
        return $value instanceof MenuLinkType ? $value : MenuLinkType::tryFrom((string) $value);
    }

    private static function isRecordLink(mixed $value): bool
    {
        return in_array(self::linkType($value), [
            MenuLinkType::Page,
            MenuLinkType::Category,
            MenuLinkType::Product,
            MenuLinkType::Post,
        ], true);
    }

    /** @return array<int, string> */
    private static function targetOptions(mixed $value): array
    {
        return match (self::linkType($value)) {
            MenuLinkType::Page => Page::query()->orderBy('title')->pluck('title', 'id')->all(),
            MenuLinkType::Category => Category::query()->orderBy('name')->pluck('name', 'id')->all(),
            MenuLinkType::Product => Product::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all(),
            MenuLinkType::Post => Post::query()->orderBy('title')->pluck('title', 'id')->all(),
            default => [],
        };
    }
}
