<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Deposits\Pages;

use App\Exceptions\DomainException;
use App\Filament\Customer\Resources\Deposits\DepositResource;
use App\Filament\Support\MoneyInput;
use App\Models\PaymentMethod;
use App\Services\DepositService;
use App\Support\Money;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Topping up the wallet.
 *
 * A wizard because the three steps genuinely depend on each other: the fee and the
 * instructions both come from the method chosen in step one.
 */
final class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    protected static ?string $title = 'Add money to your wallet';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Method')
                    ->schema([
                        Radio::make('payment_method_id')
                            ->label('How would you like to pay?')
                            ->options(fn (): array => PaymentMethod::forDeposits()->pluck('name', 'id')->all())
                            ->descriptions(fn (): array => PaymentMethod::forDeposits()
                                ->get()
                                ->mapWithKeys(fn (PaymentMethod $m): array => [$m->id => (string) $m->instructions])
                                ->all())
                            ->required()
                            ->live(),
                    ]),

                Step::make('Amount')
                    ->schema([
                        MoneyInput::make('amount')
                            ->required()
                            ->live(onBlur: true)
                            ->helperText(fn (Get $get): string => self::limitsFor($get('payment_method_id'))),
                        Placeholder::make('fee_preview')
                            ->label('You will pay')
                            ->content(function (Get $get): string {
                                $method = PaymentMethod::find($get('payment_method_id'));
                                $amount = (int) $get('amount');

                                if ($method === null || $amount <= 0) {
                                    return '—';
                                }

                                $fee = $method->feeFor($amount);

                                return $fee > 0
                                    ? Money::format($amount + $fee).' (includes a '.Money::format($fee).' fee)'
                                    : Money::format($amount);
                            }),
                    ]),

                Step::make('Confirm')
                    ->schema([
                        Placeholder::make('instructions')
                            ->label('Send the money to')
                            ->content(function (Get $get) {
                                $method = PaymentMethod::find($get('payment_method_id'));

                                if ($method === null) {
                                    return '—';
                                }

                                return $method->account_number
                                    ? $method->account_number.' ('.$method->account_type.')'
                                    : 'You will be redirected to the secure payment page.';
                            }),
                        TextInput::make('sender_number')
                            ->label('The number you sent from')
                            ->placeholder('01XXXXXXXXX')
                            ->rule('regex:/^01\d{9}$/')
                            ->visible(fn (Get $get): bool => PaymentMethod::find($get('payment_method_id'))?->isManual() ?? false)
                            ->required(fn (Get $get): bool => PaymentMethod::find($get('payment_method_id'))?->isManual() ?? false),
                        TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->visible(fn (Get $get): bool => PaymentMethod::find($get('payment_method_id'))?->isManual() ?? false)
                            ->required(fn (Get $get): bool => PaymentMethod::find($get('payment_method_id'))?->isManual() ?? false),
                        FileUpload::make('screenshot')
                            ->label('Payment screenshot (optional)')
                            ->image()
                            ->maxSize(5120)
                            // Private disk: a receipt carries a phone number and an amount.
                            ->disk('local')
                            ->directory('deposits')
                            ->visible(fn (Get $get): bool => PaymentMethod::find($get('payment_method_id'))?->isManual() ?? false),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $method = PaymentMethod::findOrFail($data['payment_method_id']);
        $service = app(DepositService::class);

        try {
            if ($method->isGateway()) {
                return $service->createGateway(auth()->user(), $method, (int) $data['amount']);
            }

            return $service->createManual(
                auth()->user(),
                $method,
                (int) $data['amount'],
                (string) $data['sender_number'],
                mb_strtoupper((string) $data['transaction_id']),
                $data['screenshot'] ?? null,
            );
        } catch (DomainException $e) {
            Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        $deposit = $this->record;

        // A gateway deposit needs the customer at shurjoPay, not back on a list.
        if ($deposit->paymentMethod?->isGateway()) {
            return route('payments.shurjopay.start', ['type' => 'deposit', 'id' => $deposit->getKey()]);
        }

        return self::getResource()::getUrl('index');
    }

    private static function limitsFor(mixed $methodId): string
    {
        $method = PaymentMethod::find($methodId);

        if ($method === null) {
            return 'Choose a payment method first.';
        }

        $min = Money::format($method->min_amount);

        return $method->max_amount
            ? "Between {$min} and ".Money::format($method->max_amount).'.'
            : "At least {$min}.";
    }
}
