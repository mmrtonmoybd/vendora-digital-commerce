<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Support\MoneyInput;
use App\Settings\AffiliateSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class AffiliateSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Referral programme';

    public static function settingsClass(): string
    {
        return AffiliateSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Programme')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')
                        ->label('Run a referral programme')
                        ->helperText('When off, the affiliate section disappears from the portal and no new commissions are created.')
                        ->live(),
                    Select::make('commission_type')
                        ->options(['percent' => 'Percentage of the order', 'fixed' => 'Fixed amount per order'])
                        ->required()
                        ->live()
                        ->native(false),
                    TextInput::make('commission_value')
                        ->label(fn (Get $get): string => $get('commission_type') === 'percent' ? 'Percentage' : 'Amount')
                        ->numeric()
                        ->required()
                        ->suffix(fn (Get $get): ?string => $get('commission_type') === 'percent' ? '%' : null)
                        ->helperText('A percentage is a whole number; a fixed amount is in taka.'),
                    Select::make('trigger')
                        ->options([
                            'first_order' => 'Only the referred customer\'s first order',
                            'every_order' => 'Every order they place',
                        ])
                        ->required()
                        ->native(false),
                    Select::make('approval')
                        ->options([
                            'auto_on_complete' => 'Approve automatically when the order completes',
                            'manual' => 'Hold for manual approval',
                        ])
                        ->required()
                        ->native(false)
                        ->helperText('Approval is what credits the referrer\'s wallet.'),
                    MoneyInput::make('min_withdrawal', 'Minimum withdrawal'),
                    TextInput::make('cookie_days')
                        ->label('Referral cookie lifetime (days)')
                        ->numeric()->required()->minValue(1)
                        ->helperText('How long after clicking a referral link a signup still counts.'),
                ]),
        ];
    }
}
