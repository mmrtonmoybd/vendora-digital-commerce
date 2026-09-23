<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SlugRedirects;

use App\Filament\Admin\Resources\SlugRedirects\Pages\ListSlugRedirects;
use App\Models\SlugRedirect;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Old addresses and where they now point.
 *
 * Most rows appear on their own: renaming a product, category, page or post slug records
 * one automatically, so links already shared or indexed keep working. They can also be
 * written by hand, which is what you want when a URL is retired from somewhere outside this
 * system entirely.
 *
 * The hit counter is the useful column. A redirect still being followed months later means
 * the old address is printed somewhere you have not found yet.
 */
final class SlugRedirectResource extends Resource
{
    protected static ?string $model = SlugRedirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'redirect';

    protected static ?string $recordTitleAttribute = 'from_path';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('from_path')
                        ->label('Old address')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->prefix(url('/'))
                        ->helperText('The path that should redirect, starting with a slash — for example /product/old-name.')
                        ->rule('starts_with:/'),

                    TextInput::make('to_path')
                        ->label('New address')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Where visitors should land instead.')
                        ->rule('starts_with:/')
                        ->different('from_path'),

                    Select::make('status_code')
                        ->label('Type')
                        ->options([
                            301 => '301 — moved permanently (passes search ranking)',
                            302 => '302 — temporary',
                        ])
                        ->default(301)
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('from_path')->label('Old address')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('to_path')->label('Now points to')->searchable()->sortable(),
                TextColumn::make('status_code')->label('Type')->badge()
                    ->color(fn (int $state): string => $state === 301 ? 'success' : 'warning'),
                TextColumn::make('hits')->label('Times followed')->sortable()->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray'),
                TextColumn::make('created_at')->label('Recorded')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status_code')->label('Type')->options([301 => '301', 302 => '302']),
                Filter::make('followed')
                    ->label('Still being followed')
                    ->query(fn (Builder $query): Builder => $query->where('hits', '>', 0)),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ActionGroup::make([EditAction::make(), DeleteAction::make()])])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSlugRedirects::route('/')];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['from_path', 'to_path'];
    }
}
