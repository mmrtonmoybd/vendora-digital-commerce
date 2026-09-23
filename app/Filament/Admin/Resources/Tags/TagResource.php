<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tags;

use App\Filament\Admin\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Blog tags.
 *
 * Managed here and attached to posts, but deliberately not rendered on the storefront: the
 * Vendora template has no tag UI, and inventing one would break the pixel-for-pixel rule
 * the rest of the shop is built to. They earn their keep as an editorial filter and in the
 * keywords a post contributes to search.
 */
final class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|null|UnitEnum $navigationGroup = 'Content';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(60)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state)))
                ->columnSpanFull(),

            TextInput::make('slug')
                ->required()
                ->maxLength(60)
                ->unique(ignoreRecord: true)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('slug')->searchable()->toggleable(),
                TextColumn::make('posts_count')->counts('posts')->label('Posts')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ActionGroup::make([EditAction::make(), DeleteAction::make()])])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTags::route('/')];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }
}
