<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Categories\Schemas;

use App\Filament\Support\Presets;
use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(140)
                            ->unique(Category::class, 'slug', ignoreRecord: true)
                            ->helperText('Used in the storefront URL.'),
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->relationship('parent', 'name', fn ($query, ?Category $record) => $record
                                ? $query->whereKeyNot($record->getKey())
                                : $query)
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for a top-level category.'),
                        TextInput::make('section_title')
                            ->helperText('Heading for this category\'s section on the home page. Defaults to the name.'),
                        Textarea::make('short_description')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Appearance')
                    ->columns(2)
                    ->schema([
                        Select::make('icon_key')
                            ->label('Icon')
                            ->options(Presets::icons())
                            ->required()
                            ->native(false),
                        Select::make('gradient')
                            ->label('Tile gradient')
                            ->options(Presets::gradients())
                            ->required()
                            ->native(false),
                        FileUpload::make('image')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Section::make('Visibility')
                    ->columns(2)
                    ->schema([
                        TextInput::make('sort_order')->numeric()->default(0)->required(),
                        Toggle::make('is_active')->default(true)->helperText('Hidden everywhere when off.'),
                        Toggle::make('show_on_home')->default(true)->helperText('Show a product section for this category on the home page.'),
                        Toggle::make('show_in_nav')->helperText('Add to the header navigation.'),
                    ]),

                Section::make('SEO')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('seo_title')->maxLength(180),
                        Textarea::make('seo_description')->rows(2)->maxLength(500),
                    ]),
            ]);
    }
}
