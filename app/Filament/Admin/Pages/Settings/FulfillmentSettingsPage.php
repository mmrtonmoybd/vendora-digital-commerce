<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\FulfillmentSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class FulfillmentSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Access delivery';

    public static function settingsClass(): string
    {
        return FulfillmentSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Queue')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('auto_assign_enabled')
                        ->label('Assign paid orders automatically')
                        ->helperText('Round-robin across staff who hold the fulfil-orders permission.'),
                    TextInput::make('auto_confirm_days')
                        ->label('Auto-confirm after (days)')
                        ->numeric()->required()->minValue(1)
                        ->helperText('Most customers accept the invitation and never tell us. Without this, those items stay open forever and the fulfilment report becomes meaningless.'),
                    TextInput::make('reinvite_daily_limit')
                        ->label('Re-invite requests per item per day')
                        ->numeric()->required()->minValue(1),
                    Toggle::make('notify_account_email')
                        ->label('Also email the account address')
                        ->helperText('When the delivery email differs from the account email, tell both.'),
                    TextInput::make('delivery_email_help')
                        ->label('Help text under the email field')
                        ->required()
                        ->columnSpanFull()
                        ->helperText('Shown at checkout. This is the one field the whole fulfilment step depends on, so say what it does.'),
                ]),
        ];
    }
}
