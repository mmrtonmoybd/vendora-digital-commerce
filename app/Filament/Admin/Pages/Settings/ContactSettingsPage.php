<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\ContactSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ContactSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Contact & social';

    public static function settingsClass(): string
    {
        return ContactSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('How customers reach you')
                ->columns(2)
                ->columnSpanFull()
                ->description('Empty fields are hidden from the storefront rather than shown blank.')
                ->schema([
                    TextInput::make('phone')->tel(),
                    TextInput::make('email')->email(),
                    TextInput::make('whatsapp')->label('WhatsApp number')->helperText('Digits only, with the country code.'),
                    TextInput::make('telegram')->label('Telegram username')->prefix('@'),
                    TextInput::make('address'),
                    Textarea::make('map_embed_url')->label('Map embed URL')->rows(2)
                        ->helperText('The src of a Google Maps embed. Leave empty to hide the map.'),
                ]),

            Section::make('Social profiles')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('facebook')->url(),
                    TextInput::make('youtube')->url(),
                    TextInput::make('discord')->url(),
                ]),

            Section::make('Floating support button')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('floating_support_target')
                        ->label('Where it goes')
                        ->options([
                            'whatsapp' => 'WhatsApp',
                            'telegram' => 'Telegram',
                            'ticket' => 'Open a support ticket',
                        ])
                        ->required()
                        ->native(false),
                    TextInput::make('floating_support_message')
                        ->label('Prefilled message')
                        ->helperText('Used for WhatsApp and Telegram links.'),
                ]),
        ];
    }
}
