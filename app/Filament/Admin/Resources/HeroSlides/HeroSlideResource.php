<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HeroSlides;

use App\Filament\Admin\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Admin\Resources\HeroSlides\Pages\EditHeroSlide;
use App\Filament\Admin\Resources\HeroSlides\Pages\ListHeroSlides;
use App\Filament\Support\Presets;
use App\Models\HeroSlide;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|null|UnitEnum $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Slide')->columns(2)->schema([
                TextInput::make('eyebrow')->label('Small label above the title')->maxLength(60),
                TextInput::make('title')->required()->maxLength(120),
                TextInput::make('subtitle')->maxLength(180)->columnSpanFull(),
                TextInput::make('button_text')->maxLength(40),
                TextInput::make('button_url')->maxLength(255)->default('#categories'),
            ]),
            Section::make('Appearance')->columns(2)->schema([
                Select::make('gradient')->label('Background')->options(Presets::heroGradients())->required()->native(false),
                Select::make('text_class')
                    ->label('Text colour')
                    ->options(['text-ink' => 'Dark text', 'text-white' => 'Light text'])
                    ->required()
                    ->native(false),
                Select::make('icon_key')->label('Icon')->options(Presets::icons())->required()->native(false),
                FileUpload::make('image')->image()->disk('public')->directory('slides')->visibility('public'),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->weight('semibold')->description(fn (HeroSlide $r): ?string => $r->subtitle),
                TextColumn::make('eyebrow')->badge()->color('gray'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
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
            ])
            ->recordActions([ActionGroup::make([EditAction::make()])])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHeroSlides::route('/'),
            'create' => CreateHeroSlide::route('/create'),
            'edit' => EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
