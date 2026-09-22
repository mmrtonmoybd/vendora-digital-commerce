<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\ThemeSettings;
use App\Support\BrandPalette;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

final class ThemeSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Appearance';

    public static function settingsClass(): string
    {
        return ThemeSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Brand colour')
                ->columns(2)
                ->columnSpanFull()
                ->description('One colour drives the whole storefront. Pick a Tailwind shade and the exact scale is used; anything else is mixed into a usable ramp.')
                ->schema([
                    ColorPicker::make('brand_hex')->label('Storefront brand')->required()->live(),
                    ColorPicker::make('admin_primary_hex')->label('Admin panel primary')->required(),
                    Placeholder::make('preview')
                        ->label('How the scale will look')
                        ->columnSpanFull()
                        ->content(function (Get $get): HtmlString {
                            $palette = BrandPalette::fromHex((string) $get('brand_hex'));
                            $swatches = '';

                            foreach ($palette as $shade => $rgb) {
                                $swatches .= sprintf(
                                    '<div style="background:rgb(%s);" class="flex h-12 flex-1 items-end justify-center pb-1 text-[10px] font-semibold %s">%s</div>',
                                    $rgb,
                                    $shade >= 500 ? 'text-white' : 'text-gray-700',
                                    $shade,
                                );
                            }

                            return new HtmlString('<div class="flex overflow-hidden rounded-lg">'.$swatches.'</div>');
                        }),
                ]),

            Section::make('Home page')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('hero_autoplay_seconds')
                        ->label('Hero slide interval (seconds)')
                        ->numeric()->required()->minValue(2)->maxValue(30),
                    TextInput::make('home_products_per_section')
                        ->label('Products per home section')
                        ->numeric()->required()->minValue(2)->maxValue(12),
                ]),
        ];
    }
}
