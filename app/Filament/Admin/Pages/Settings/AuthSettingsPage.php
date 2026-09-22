<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\AuthSettings;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class AuthSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Customer accounts';

    public static function settingsClass(): string
    {
        return AuthSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Registration')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('registration_enabled')
                        ->label('Allow new accounts')
                        ->helperText('When off, the register page returns 404 and the link disappears.'),
                    Toggle::make('require_email_verification')
                        ->label('Require email verification')
                        ->helperText('Unverified customers cannot open the portal.'),
                ]),

            Section::make('Sign in with Google')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('google_login_enabled')->label('Enabled')->live(),
                    Placeholder::make('redirect_uri')
                        ->label('Authorised redirect URI')
                        ->content(fn (): string => route('auth.google.callback'))
                        ->visible(fn (Get $get): bool => (bool) $get('google_login_enabled'))
                        ->helperText('Paste this into the Google Cloud console exactly as shown.'),
                    TextInput::make('google_client_id')
                        ->autocomplete(false)
                        ->visible(fn (Get $get): bool => (bool) $get('google_login_enabled')),
                    TextInput::make('google_client_secret')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('Stored encrypted.')
                        ->visible(fn (Get $get): bool => (bool) $get('google_login_enabled')),
                ]),
        ];
    }
}
