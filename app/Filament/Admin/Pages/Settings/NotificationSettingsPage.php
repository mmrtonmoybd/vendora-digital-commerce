<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Services\NotificationDispatcher;
use App\Settings\TelegramSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Telegram alerts to the back office, so staff notice work without watching the panel. */
final class NotificationSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Staff alerts';

    public static function settingsClass(): string
    {
        return TelegramSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Telegram')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')->label('Send Telegram alerts')->live(),
                    TextInput::make('bot_token')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('From @BotFather. Stored encrypted.')
                        ->visible(fn (Get $get): bool => (bool) $get('enabled')),
                    TextInput::make('chat_id')
                        ->helperText('The group or channel the bot posts to.')
                        ->visible(fn (Get $get): bool => (bool) $get('enabled')),
                ]),

            Section::make('What to alert on')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('on_new_order')->label('A new order is placed'),
                    Toggle::make('on_payment_proof')->label('A payment receipt is submitted'),
                    Toggle::make('on_new_deposit')->label('A deposit needs review'),
                    Toggle::make('on_new_ticket')->label('A ticket is opened or replied to'),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Send a test alert')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action(function (): void {
                    app(NotificationDispatcher::class)
                        ->telegram('Test alert from your shop. If you can read this, alerts are working.');

                    Notification::make()
                        ->info()
                        ->title('Test sent')
                        ->body('Check the Telegram chat. Nothing arrives if the token or chat id is wrong.')
                        ->send();
                }),
        ];
    }
}
