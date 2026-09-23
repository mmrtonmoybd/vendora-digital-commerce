<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts;

use App\Filament\Admin\Resources\Posts\Pages\CreatePost;
use App\Filament\Admin\Resources\Posts\Pages\EditPost;
use App\Filament\Admin\Resources\Posts\Pages\ListPosts;
use App\Filament\Support\Presets;
use App\Filament\Support\TranslateAction;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

final class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|null|UnitEnum $navigationGroup = 'Content';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Article')->columns(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->required()->maxLength(220)->unique(Post::class, 'slug', ignoreRecord: true),
                Select::make('blog_category_id')->label('Category')->relationship('blogCategory', 'name')->searchable()->preload(),
                Textarea::make('excerpt')->rows(2)->maxLength(400)->columnSpanFull(),
                RichEditor::make('body')->columnSpanFull(),
            ]),
            Section::make('Publishing')->columns(2)->schema([
                Toggle::make('is_published'),
                DateTimePicker::make('published_at')->seconds(false)->default(now()),
                FileUpload::make('cover')->image()->disk('public')->directory('posts')->visibility('public'),
                Select::make('gradient')->label('Fallback gradient')->options(Presets::gradients())->required()->native(false)
                    ->helperText('Used when there is no cover image.'),
                Select::make('tags')->relationship('tags', 'name')->multiple()->searchable()->preload()->columnSpanFull(),
            ]),
            Section::make('SEO')->columns(2)->collapsed()->schema([
                TextInput::make('seo_title')->maxLength(180),
                Textarea::make('seo_description')->rows(2)->maxLength(500),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->weight('semibold')->wrap()->limit(70),
                TextColumn::make('blogCategory.name')->label('Category')->badge()->color('gray'),
                IconColumn::make('is_published')->label('Live')->boolean(),
                TextColumn::make('published_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('views')->badge()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('blog_category')->relationship('blogCategory', 'name')->preload(),
                TernaryFilter::make('is_published')->label('Published'),
                Filter::make('published_at')
                    ->schema([
                        DatePicker::make('from')->label('Published from')->native(false),
                        DatePicker::make('until')->label('Published until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('published_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('published_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Published from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Published until '.$data['until'] : null,
                    ]))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    TranslateAction::make(),
                    Action::make('view')
                        ->label('View on site')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->visible(fn (Post $record): bool => $record->is_published)
                        ->url(fn (Post $record): string => route('blog.show', $record), shouldOpenInNewTab: true),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug'];
    }
}
