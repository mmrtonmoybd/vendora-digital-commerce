<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\MailSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

/** SMTP credentials, applied to the mailer at runtime rather than read from .env. */
final class MailSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Email';

    public static function settingsClass(): string
    {
        return MailSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Sending')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('mailer')
                        ->options([
                            'smtp' => 'SMTP',
                            'log' => 'Write to the log (testing)',
                            'sendmail' => 'Sendmail',
                        ])
                        ->required()
                        ->live()
                        ->native(false),
                    TextInput::make('from_address')->label('From address')->email()->required(),
                    TextInput::make('from_name')->label('From name')->required(),
                ]),

            Section::make('SMTP server')
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('mailer') === 'smtp')
                ->schema([
                    TextInput::make('host')->required(fn (Get $get): bool => $get('mailer') === 'smtp'),
                    TextInput::make('port')->numeric()->required(fn (Get $get): bool => $get('mailer') === 'smtp'),
                    TextInput::make('username')->autocomplete(false),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('Stored encrypted.'),
                    Select::make('encryption')
                        ->options(['tls' => 'TLS', 'ssl' => 'SSL'])
                        ->placeholder('None')
                        ->native(false),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Send a test email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->schema([
                    TextInput::make('to')
                        ->email()
                        ->required()
                        ->default(fn (): ?string => auth('admin')->user()?->email),
                ])
                // Uses the saved settings, so a failure here is the same failure a customer
                // would hit — which is the point of the button.
                ->action(function (array $data): void {
                    try {
                        Mail::raw(
                            'This is a test from your shop. If you are reading it, email is working.',
                            fn ($message) => $message->to($data['to'])->subject('Test email'),
                        );

                        Notification::make()->success()->title('Test email sent')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Could not send')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}
