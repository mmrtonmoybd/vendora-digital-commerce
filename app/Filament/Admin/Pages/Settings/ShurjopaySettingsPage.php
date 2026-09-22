<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Payments\Gateways\ShurjopayGateway;
use App\Services\ShurjopayService;
use App\Settings\ShurjopaySettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * shurjoPay credentials.
 *
 * Everything the gateway needs lives here rather than in the environment, so moving
 * between sandbox and live is a dropdown rather than a deploy. The callback URLs are shown
 * read-only because they have to be registered in the shurjoPay merchant panel, and
 * getting one character wrong there is a class of outage that is very hard to diagnose.
 */
final class ShurjopaySettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Payment gateway';

    public static function settingsClass(): string
    {
        return ShurjopaySettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Connection')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')
                        ->label('Accept online card payments')
                        ->helperText('When off, the gateway option disappears from checkout.'),
                    Select::make('mode')
                        ->options(['sandbox' => 'Sandbox (testing)', 'live' => 'Live (real money)'])
                        ->required()
                        ->live()
                        ->native(false),
                    Placeholder::make('mode_warning')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('mode') === 'live')
                        ->content(new HtmlString(
                            '<div class="rounded-lg bg-danger-50 p-3 text-sm font-medium text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">'
                            .'Live mode takes real money. Sandbox credentials will not work here, and live credentials will not work in sandbox.'
                            .'</div>'
                        )),
                    TextInput::make('api_url')
                        ->label('API URL')
                        ->required()
                        ->url()
                        ->helperText('Sandbox: https://sandbox.shurjopayment.com · Live: the URL shurjoPay gave you.'),
                    TextInput::make('username')->required()->autocomplete(false),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('Stored encrypted. Never written to a log or a stored payload.'),
                    TextInput::make('prefix')
                        ->required()
                        ->maxLength(5)
                        ->helperText('At most 5 characters — shurjoPay rejects longer ones.'),
                    Select::make('currency')->options(['BDT' => 'BDT', 'USD' => 'USD'])->required()->native(false),
                    Toggle::make('ssl_verify')
                        ->label('Verify the SSL certificate')
                        ->helperText('Leave on. Turning it off in production would accept any certificate.'),
                    TextInput::make('fee_bps')
                        ->label('Surcharge (basis points)')
                        ->numeric()->minValue(0)
                        ->helperText('100 bps = 1%. Added to the amount charged through the gateway.'),
                ]),

            Section::make('Register these URLs with shurjoPay')
                ->columnSpanFull()
                ->description('Paste these into the shurjoPay merchant panel exactly as shown.')
                ->schema([
                    Placeholder::make('callbacks')
                        ->hiddenLabel()
                        ->content(function (): HtmlString {
                            $rows = '';

                            foreach (app(ShurjopayService::class)->callbackUrls() as $label => $url) {
                                $rows .= sprintf(
                                    '<div class="flex flex-col gap-0.5 py-1"><span class="text-xs font-medium text-gray-500">%s</span><code class="text-sm">%s</code></div>',
                                    e(str($label)->headline()),
                                    e($url),
                                );
                            }

                            return new HtmlString($rows);
                        }),
                ]),

            Section::make('Customer defaults')
                ->columns(3)
                ->columnSpanFull()
                ->description('shurjoPay requires an address on every transaction. These fill in for guests.')
                ->schema([
                    TextInput::make('customer_city')->required(),
                    TextInput::make('customer_postcode')->required(),
                    TextInput::make('customer_country')->required(),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test connection')
                ->icon('heroicon-o-signal')
                ->color('gray')
                // Authenticates against whichever environment is currently saved, so the
                // operator finds out here rather than from a failed customer payment.
                ->action(function (): void {
                    $result = app(ShurjopayGateway::class)->testConnection();

                    $result['ok']
                        ? Notification::make()->success()->title('Connected')->body($result['message'])->send()
                        : Notification::make()->danger()->title('Could not connect')->body($result['message'])->send();
                }),
        ];
    }
}
