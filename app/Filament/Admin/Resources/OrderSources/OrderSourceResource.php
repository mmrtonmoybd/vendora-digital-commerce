<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderSources;

use App\Filament\Admin\Resources\OrderSources\Pages\CreateOrderSource;
use App\Filament\Admin\Resources\OrderSources\Pages\EditOrderSource;
use App\Filament\Admin\Resources\OrderSources\Pages\ListOrderSources;
use App\Models\OrderSource;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The channels an order can be attributed to.
 *
 * CaptureSource matches an incoming visit against these rows — first on the click id
 * (fbclid, ttclid, gclid), then on utm_source, then on the referring host — so adding a
 * newsletter or an influencer's short link is a row here, not a deploy.
 *
 * The order count is the column that matters: it is the answer to "which platform is
 * actually selling", which is why the attribution exists at all.
 */
final class OrderSourceResource extends Resource
{
    protected static ?string $model = OrderSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'traffic source';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Channel')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->required()->maxLength(60),

                    TextInput::make('key')
                        ->maxLength(60)
                        ->unique(ignoreRecord: true)
                        ->helperText('Left empty this is made from the name. Used in reports and exports, so changing it later rewrites how older orders group.'),

                    ColorPicker::make('color')->helperText('Used on the badge in the orders table and the source report.'),

                    TextInput::make('icon_key')
                        ->label('Icon')
                        ->maxLength(40)
                        ->helperText('A key from the storefront icon set.'),

                    TextInput::make('sort_order')->numeric()->default(0),
                ]),

            Section::make('How a visit is matched')
                ->description('Checked in order: click id, then utm_source, then the referring host. The first rule that matches wins.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TagsInput::make('aliases')
                        ->label('utm_source values and click ids')
                        ->placeholder('facebook, fb, ig, fbclid')
                        ->helperText('Press Enter after each. Case is ignored.')
                        ->columnSpanFull(),

                    TagsInput::make('hosts')
                        ->label('Referring hosts')
                        ->placeholder('facebook.com, m.facebook.com, l.facebook.com')
                        ->helperText('Matched against the domain a visitor arrived from, including subdomains.')
                        ->columnSpanFull(),
                ]),

            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')->label('Active')->default(true),

                    Toggle::make('is_default')
                        ->label('Use for unmatched visits')
                        ->helperText('Exactly one source carries this. Turning it on here turns it off everywhere else.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('key')->searchable()->toggleable(),
                TextColumn::make('orders_count')->counts('orders')->label('Orders')->badge()->sortable(),
                TextColumn::make('aliases')->label('Matches')->badge()->limitList(3)->toggleable(),
                TextColumn::make('hosts')->label('Referrers')->badge()->limitList(2)->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_default')->label('Fallback')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('is_default')->label('Fallback source'),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()])])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderSources::route('/'),
            'create' => CreateOrderSource::route('/create'),
            'edit' => EditOrderSource::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'key'];
    }
}
