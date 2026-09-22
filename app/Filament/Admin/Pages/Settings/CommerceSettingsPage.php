<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\CommerceSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class CommerceSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Checkout rules';

    public static function settingsClass(): string
    {
        return CommerceSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Currency')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('currency_symbol')->required()->maxLength(4),
                    TextInput::make('currency_code')->required()->maxLength(3)
                        ->helperText('Used by the payment gateway and the structured data search engines read.'),
                ]),

            Section::make('Orders')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('order_prefix')->required()->maxLength(6)
                        ->helperText('Order numbers look like PREFIX-2601-000001.'),
                    TextInput::make('auto_cancel_unpaid_minutes')
                        ->label('Cancel unpaid orders after (minutes)')
                        ->numeric()->required()->minValue(5)
                        ->helperText('Stock is reserved when an order is placed, so unpaid orders must release it.'),
                    TextInput::make('max_cart_items')->numeric()->required()->minValue(1),
                    TextInput::make('low_stock_threshold')
                        ->numeric()->required()->minValue(0)
                        ->helperText('Drives the low-stock warning on the dashboard.'),
                    Toggle::make('guest_checkout_enabled')
                        ->label('Allow checkout without an account')
                        ->helperText('Guests get a signed link to their order by email.'),
                    Toggle::make('restore_stock_on_refund')
                        ->label('Put stock back on a full refund'),
                ]),
        ];
    }
}
