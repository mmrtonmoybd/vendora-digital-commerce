<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Enums\FieldType;
use App\Filament\Support\MoneyInput;
use App\Filament\Support\Presets;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Product')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('General')->schema(self::general()),
                        Tab::make('Pricing & stock')->schema(self::pricing()),
                        Tab::make('Access delivery')->schema(self::accessDelivery()),
                        Tab::make('Checkout fields')->schema(self::checkoutFields()),
                        Tab::make('Content')->schema(self::content()),
                        Tab::make('SEO')->schema(self::seo()),
                    ]),
            ]);
    }

    /** @return array<int, mixed> */
    private static function general(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(180)
                ->columnSpanFull()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')
                ->required()
                ->maxLength(200)
                ->unique(Product::class, 'slug', ignoreRecord: true),
            Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('short')
                ->label('Card subtitle')
                ->maxLength(180)
                ->columnSpanFull()
                ->helperText('The one-line description shown under the product name.'),
            Select::make('icon_key')->label('Icon')->options(Presets::icons())->required()->native(false),
            Select::make('gradient')->label('Card gradient')->options(Presets::gradients())->required()->native(false),
            TextInput::make('badge_text')
                ->label('Corner ribbon')
                ->maxLength(40)
                ->helperText('e.g. BEST SELLING. Leave empty for no ribbon.'),
            Select::make('badge_style')->label('Ribbon style')->options(Presets::badgeStyles())->default('brand')->native(false),
            FileUpload::make('image')
                ->image()
                ->disk('public')
                ->directory('products')
                ->visibility('public')
                ->imageEditor(),
            FileUpload::make('gallery')
                ->image()
                ->multiple()
                ->maxFiles(4)
                ->disk('public')
                ->directory('products')
                ->visibility('public')
                ->reorderable(),
            Toggle::make('is_active')->default(true),
            Toggle::make('is_featured'),
            TextInput::make('sort_order')->numeric()->default(0)->required(),
        ];
    }

    /** @return array<int, mixed> */
    private static function pricing(): array
    {
        return [
            MoneyInput::make('price', 'Sale price')->required(),
            MoneyInput::make('regular_price', 'Regular price')
                ->helperText('Shown struck through. Leave empty to hide the discount.'),
            TextInput::make('stock')->numeric()->required()->default(0)->minValue(0)
                ->helperText('Drives the "n pcs" pill and the Out of Stock button.'),
            TextInput::make('min_qty')->label('Minimum per order')->numeric()->required()->default(1)->minValue(1),
            TextInput::make('max_qty')->label('Maximum per order')->numeric()->required()->default(10)->minValue(1),
            TextInput::make('rating')
                ->label('Rating (tenths)')
                ->numeric()->default(49)->minValue(0)->maxValue(50)
                ->helperText('Stored as tenths so no float is involved: 49 renders as 4.9.'),
            TextInput::make('reviews_count')->label('Review count')->numeric()->default(0)->minValue(0),
        ];
    }

    /** @return array<int, mixed> */
    private static function accessDelivery(): array
    {
        return [
            Select::make('platform_id')
                ->label('Platform')
                ->relationship('platform', 'name')
                ->searchable()
                ->preload()
                ->helperText('Where access is granted: TikTok Business Center, Meta Business Manager…'),
            TextInput::make('access_role')
                ->maxLength(120)
                ->helperText('What the customer is told they receive, e.g. "Admin access".'),
            TextInput::make('fulfillment_sla_minutes')
                ->label('Delivery target (minutes)')
                ->numeric()->default(30)->required()->minValue(1)
                ->helperText('Shown to the customer and used by the SLA timer in the access queue.'),
            TextInput::make('warranty_days')
                ->label('Replacement window (days)')
                ->numeric()->minValue(0)
                ->helperText('How long a customer may report lost access. Empty means no window.'),
            RichEditor::make('customer_instructions')
                ->label('Instructions shown after purchase')
                ->columnSpanFull(),
            Toggle::make('requires_delivery_email')
                ->default(true)
                ->helperText('The email entered at checkout is the address that receives access.'),
        ];
    }

    /** @return array<int, mixed> */
    private static function checkoutFields(): array
    {
        return [
            Repeater::make('fields')
                ->relationship()
                ->label('Extra details to collect at checkout')
                ->helperText('Anything you need in order to grant access — a Player ID, a Business Center ID, an account link.')
                ->columnSpanFull()
                ->orderColumn('sort_order')
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                ->defaultItems(0)
                ->columns(2)
                ->schema([
                    TextInput::make('label')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('key', Str::slug($state ?? '', '_'))),
                    TextInput::make('key')
                        ->required()
                        ->maxLength(60)
                        ->helperText('Stored with the order. Letters, numbers and underscores.'),
                    Select::make('type')->options(FieldType::class)->required()->default(FieldType::Text)->live()->native(false),
                    TextInput::make('placeholder'),
                    Repeater::make('options')
                        ->simple(TextInput::make('option')->required())
                        ->visible(fn (Get $get): bool => $get('type') === FieldType::Select->value)
                        ->columnSpanFull(),
                    Toggle::make('required'),
                ]),
        ];
    }

    /** @return array<int, mixed> */
    private static function content(): array
    {
        return [
            Textarea::make('features')
                ->label('Feature bullets')
                ->rows(5)
                ->columnSpanFull()
                ->helperText('One per line. Rendered as the tick list on the product page.'),
            RichEditor::make('description')->columnSpanFull(),
            RichEditor::make('delivery_info')->label('Delivery notes')->columnSpanFull(),
            Select::make('faqs')
                ->relationship('faqs', 'question')
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),
            Select::make('related')
                ->relationship('related', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull()
                ->helperText('Leave empty to fall back to other products in the same category.'),
        ];
    }

    /** @return array<int, mixed> */
    private static function seo(): array
    {
        return [
            TextInput::make('seo_title')->maxLength(180),
            Textarea::make('seo_description')->rows(3)->maxLength(500),
        ];
    }
}
