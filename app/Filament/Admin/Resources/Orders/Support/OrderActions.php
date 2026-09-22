<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Support;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Exceptions\DomainException;
use App\Filament\Support\MoneyInput;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\ShurjopayService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

/**
 * Every way staff can move an order, in one place.
 *
 * The table and the view page offer the same set, so an operator never has to remember
 * which screen a particular action lives on. Each one delegates to OrderService — nothing
 * here writes a status column itself — and each one that moves money asks for a reason,
 * which ends up in the order history and the activity log.
 */
final class OrderActions
{
    /** @return array<int, Action> */
    public static function all(): array
    {
        return [
            self::verifyManualPayment(),
            self::rejectProof(),
            self::markPaidManually(),
            self::startProcessing(),
            self::complete(),
            self::reverifyGateway(),
            self::refund(),
            self::cancel(),
            self::invoice(),
            self::printInvoice(),
        ];
    }

    /** Staff has checked the TrxID against the mobile-banking statement. */
    public static function verifyManualPayment(): Action
    {
        return Action::make('verifyManualPayment')
            ->label('Verify payment')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Order $record): bool => $record->payment_status === OrderPaymentStatus::Submitted)
            ->requiresConfirmation()
            ->modalDescription(fn (Order $record): string => sprintf(
                'Confirm that %s was received from %s (TrxID %s).',
                Money::format($record->total),
                $record->manual_sender_number ?? 'the customer',
                $record->manual_transaction_id ?? '—',
            ))
            ->action(fn (Order $record) => self::run(function () use ($record): void {
                $order = app(OrderService::class)->markPaid($record, 'manual', auth('admin')->user());
                OrderPaid::dispatch($order);

                Notification::make()->success()->title('Payment verified')->send();
            }));
    }

    public static function rejectProof(): Action
    {
        return Action::make('rejectProof')
            ->label('Reject proof')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Order $record): bool => $record->payment_status === OrderPaymentStatus::Submitted)
            ->schema([
                Textarea::make('reason')
                    ->required()
                    ->rows(3)
                    ->helperText('The customer sees this, so say what was wrong with the receipt.'),
            ])
            ->action(fn (Order $record, array $data) => self::run(function () use ($record, $data): void {
                app(OrderService::class)->rejectManualProof($record, auth('admin')->user(), $data['reason']);

                Notification::make()->warning()->title('Proof rejected')->send();
            }));
    }

    /**
     * Settle an order the system could not settle itself.
     *
     * This exists because reality does not always reach us cleanly: the gateway times out
     * after the customer has been charged, money arrives through a channel the form never
     * saw, or a dispute is decided in the customer's favour. The reason is mandatory and is
     * written to both the order history and the activity log.
     */
    public static function markPaidManually(): Action
    {
        return Action::make('markPaidManually')
            ->label('Mark as paid')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->visible(fn (Order $record): bool => ! $record->isPaid())
            ->schema([
                Textarea::make('reason')
                    ->label('Why are you approving this manually?')
                    ->required()
                    ->rows(3)
                    ->placeholder('e.g. gateway timed out but the bank statement shows the payment'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Approve this payment manually')
            ->modalDescription(fn (Order $record): string => 'This marks '.Money::format($record->total).' as received without a gateway confirmation.')
            ->action(fn (Order $record, array $data) => self::run(function () use ($record, $data): void {
                app(OrderService::class)->markPaidByAdmin($record, auth('admin')->user(), $data['reason']);

                Notification::make()->success()->title('Order marked as paid')->send();
            }));
    }

    public static function startProcessing(): Action
    {
        return Action::make('startProcessing')
            ->label('Start processing')
            ->icon('heroicon-o-cog-6-tooth')
            ->visible(fn (Order $record): bool => $record->isPaid() && $record->status === OrderStatus::Pending)
            ->action(fn (Order $record) => self::run(function () use ($record): void {
                app(OrderService::class)->startProcessing($record, auth('admin')->user());

                Notification::make()->success()->title('Marked as processing')->send();
            }));
    }

    public static function complete(): Action
    {
        return Action::make('complete')
            ->label('Complete order')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Order $record): bool => $record->isPaid() && ! $record->status->isFinal())
            ->schema([
                RichEditor::make('delivery_note')
                    ->label('Note for the customer')
                    ->helperText('Shown on their order page and included in the completion email.'),
                Toggle::make('notify')->label('Email the customer')->default(true),
            ])
            ->action(fn (Order $record, array $data) => self::run(function () use ($record, $data): void {
                app(OrderService::class)->complete($record, $data['delivery_note'] ?? null, auth('admin')->user());

                Notification::make()->success()->title('Order completed')->send();
            }));
    }

    /** Ask shurjoPay again about a payment we never got a clean answer for. */
    public static function reverifyGateway(): Action
    {
        return Action::make('reverifyGateway')
            ->label('Re-verify with gateway')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (Order $record): bool => ! $record->isPaid() && $record->payments()->whereNotNull('sp_order_id')->exists())
            ->action(fn (Order $record) => self::run(function () use ($record): void {
                $payment = $record->payments()->whereNotNull('sp_order_id')->latest()->first();

                $result = app(ShurjopayService::class)->verifyAndApply((string) $payment->sp_order_id, 'admin');

                $result?->isPaid()
                    ? Notification::make()->success()->title('Gateway confirms this is paid')->send()
                    : Notification::make()->warning()->title('Gateway has no confirmed payment')
                        ->body($result?->sp_message ?? 'No answer from the gateway.')->send();
            }));
    }

    public static function refund(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->visible(fn (Order $record): bool => $record->isPaid() && $record->refundableAmount() > 0)
            ->schema([
                MoneyInput::make('amount', 'Amount to refund')
                    ->required()
                    ->helperText(fn (Order $record): string => 'Up to '.Money::format($record->refundableAmount()).' can still be refunded.')
                    ->default(fn (Order $record): int => $record->refundableAmount()),
                Textarea::make('reason')->required()->rows(2),
            ])
            ->requiresConfirmation()
            ->modalDescription('The money goes back to the customer\'s wallet.')
            ->action(fn (Order $record, array $data) => self::run(function () use ($record, $data): void {
                app(OrderService::class)->refund($record, (int) $data['amount'], $data['reason'], auth('admin')->user());

                Notification::make()->success()->title('Refund issued')->send();
            }));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Cancel order')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (Order $record): bool => ! $record->status->isFinal())
            ->schema([Textarea::make('reason')->required()->rows(2)])
            ->requiresConfirmation()
            ->modalDescription('Stock goes back on the shelf. A paid order is refunded to the wallet.')
            ->action(fn (Order $record, array $data) => self::run(function () use ($record, $data): void {
                app(OrderService::class)->cancel($record, $data['reason'], auth('admin')->user());

                Notification::make()->success()->title('Order cancelled')->send();
            }));
    }

    public static function invoice(): Action
    {
        return Action::make('invoice')
            ->label('Download invoice')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn (Order $record): bool => $record->isPaid())
            ->url(fn (Order $record): string => route('admin.order.invoice', $record), shouldOpenInNewTab: true);
    }

    public static function printInvoice(): Action
    {
        return Action::make('printInvoice')
            ->label('Print invoice')
            ->icon('heroicon-o-printer')
            ->visible(fn (Order $record): bool => $record->isPaid())
            ->url(fn (Order $record): string => route('admin.order.invoice.print', $record), shouldOpenInNewTab: true);
    }

    /** Turn a business-rule refusal into a notification instead of a stack trace. */
    private static function run(callable $callback): void
    {
        try {
            $callback();
        } catch (DomainException $e) {
            Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
        }
    }
}
