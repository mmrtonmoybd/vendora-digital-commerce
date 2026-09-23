<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\LocaleSettings;
use App\Support\Locales;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class LocaleSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Languages';

    public static function settingsClass(): string
    {
        return LocaleSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Languages you offer')
                ->description('Turning a language off hides it immediately, including for visitors who had already chosen it. Their translations are kept, so turning it back on restores everything.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('enabled_locales')
                        ->label('Available languages')
                        ->options(self::localeOptions())
                        ->required()
                        ->minItems(1)
                        ->columns(2)
                        ->columnSpanFull(),

                    Select::make('default_locale')
                        ->label('Default language')
                        ->options(self::localeOptions())
                        ->required()
                        ->native(false)
                        ->helperText('Used for visitors whose browser asks for a language you do not offer, and for the text stored on products, pages and settings.'),

                    Toggle::make('auto_detect')
                        ->label('Detect the visitor\'s language')
                        ->helperText('Reads the browser\'s Accept-Language header. A visitor who picks a language keeps it. Costs you the page cache on a visitor\'s very first request — and on every search-engine crawl, since crawlers send no cookies. Turn it off and everyone starts in the default language, but every request is cacheable.'),
                ]),

            Section::make('Where the choice applies')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('show_switcher')
                        ->label('Show the switcher on the shop')
                        ->helperText('In the top strip and the mobile menu.'),

                    Toggle::make('apply_to_customer_panel')
                        ->label('Also in the customer panel'),

                    Toggle::make('apply_to_admin_panel')
                        ->label('Also in the admin panel')
                        ->helperText('Off by default: staff screens are only partly translated, and a shared machine switching language mid-shift helps nobody.'),
                ]),
        ];
    }

    /** @return array<string, string> */
    private static function localeOptions(): array
    {
        $options = [];

        foreach (Locales::SUPPORTED as $code => $meta) {
            $options[$code] = $meta['flag'].'  '.$meta['native'].' — '.$meta['english'];
        }

        return $options;
    }
}
