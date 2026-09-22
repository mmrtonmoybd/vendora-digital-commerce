<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\SecuritySettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class SecuritySettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Security';

    public static function settingsClass(): string
    {
        return SecuritySettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Admin panel access')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Textarea::make('admin_ip_allowlist')
                        ->label('Restrict the panel to these addresses')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Comma-separated IPs or CIDR blocks. Leave empty for no restriction — an allowlist that locks out the only administrator is worse than the risk it removes.'),
                    Toggle::make('force_two_factor_for_super_admin')
                        ->label('Require two-factor for super admins')
                        ->helperText('A super admin without it can only reach their own profile page until they set it up.'),
                    TextInput::make('admin_session_timeout_minutes')
                        ->label('Session timeout (minutes)')
                        ->numeric()->required()->minValue(15),
                    Toggle::make('daily_summary_email_enabled')
                        ->label('Email super admins a daily summary'),
                ]),
        ];
    }
}
