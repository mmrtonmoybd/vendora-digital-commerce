<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class GeneralSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Shop identity';

    public static function settingsClass(): string
    {
        return GeneralSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Identity')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('site_name')
                        ->required()
                        ->maxLength(80)
                        ->helperText('Shown in the header, the footer, emails and the browser tab.'),
                    TextInput::make('tagline')->required()->maxLength(200),
                    TextInput::make('support_hours')->required()->maxLength(80)->placeholder('Support 9AM – 2AM'),
                    TextInput::make('copyright_text')->required()->maxLength(200),
                ]),

            Section::make('Branding')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('logo')->image()->disk('public')->directory('branding')->visibility('public'),
                    FileUpload::make('logo_footer')->label('Footer logo')->image()->disk('public')->directory('branding')->visibility('public'),
                    FileUpload::make('favicon')->image()->disk('public')->directory('branding')->visibility('public'),
                ]),
        ];
    }
}
