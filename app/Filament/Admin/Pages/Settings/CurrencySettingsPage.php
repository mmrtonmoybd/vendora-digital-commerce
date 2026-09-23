<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\CurrencySettings;
use App\Support\Currencies;
use App\Support\Money;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Which currencies customers may see prices in.
 *
 * Taka is the shop's money and cannot be turned off: every amount is stored, charged and
 * refunded in paisa, and the gateway settles in BDT. Anything else on this screen is a
 * convenience for the customer's eyes — the charge is always taken in taka, and the
 * checkout says so.
 */
final class CurrencySettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Currencies';

    public static function settingsClass(): string
    {
        return CurrencySettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Currencies you show prices in')
                ->description('Taka is the shop\'s currency and is always available. Anything else converts from it for display only — customers are still charged in taka.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('enabled_currencies')
                        ->label('Available currencies')
                        ->options(self::currencyOptions())
                        ->disableOptionWhen(fn (string $value): bool => $value === Currencies::BASE)
                        ->default([Currencies::BASE])
                        ->required()
                        ->columns(2)
                        ->columnSpanFull(),

                    Select::make('default_currency')
                        ->label('Shown by default')
                        ->options(self::currencyOptions())
                        ->required()
                        ->native(false)
                        ->helperText('What a visitor sees before they choose anything.'),

                    Toggle::make('show_switcher')
                        ->label('Show the switcher on the shop')
                        ->helperText('In the top strip and the mobile menu, beside the language switch.'),
                ]),

            Section::make('Exchange rates')
                ->description('How many taka make one unit of the currency. Keep these current — a stale rate quotes a price you did not mean.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('rates.USD')
                        ->label('Taka per 1 US dollar')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->prefix('৳')
                        // Stored as paisa so no float ever enters a conversion.
                        ->formatStateUsing(fn (?int $state): string => Money::toDecimal((int) ($state ?? 12000)))
                        ->dehydrateStateUsing(fn (mixed $state): int => Money::fromDecimal((string) $state))
                        ->helperText('For example 120 means ৳120 to the dollar.'),

                    Toggle::make('show_settlement_note')
                        ->label('Warn at checkout that the charge is in taka')
                        ->helperText('Strongly recommended. A customer quoted in dollars and charged in taka who was not told is a chargeback waiting to happen.'),
                ]),
        ];
    }

    /** @return array<string, string> */
    private static function currencyOptions(): array
    {
        $options = [];

        foreach (Currencies::SUPPORTED as $code => $meta) {
            $label = $meta['symbol'].'  '.$code.' — '.$meta['name'];
            $options[$code] = $code === Currencies::BASE ? $label.' (always on)' : $label;
        }

        return $options;
    }
}
