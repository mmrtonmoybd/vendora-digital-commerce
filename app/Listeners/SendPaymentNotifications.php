<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DepositApproved;
use App\Events\DepositRejected;
use App\Events\DepositSubmitted;
use App\Events\InvoiceCreated;
use App\Events\InvoicePaid;
use App\Events\PaymentFailed;
use App\Services\NotificationDispatcher;
use App\Services\SmsService;
use App\Settings\SmsSettings;
use App\Support\Money;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Wallet top-ups, invoices and gateway failures. */
final class SendPaymentNotifications implements ShouldQueue
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly SmsService $sms,
        private readonly SmsSettings $smsSettings,
    ) {}

    public function handleDepositSubmitted(DepositSubmitted $event): void
    {
        $deposit = $event->deposit;

        $this->notifications->toCustomer(
            $deposit->user,
            'deposit_submitted',
            [
                'deposit_number' => $deposit->deposit_number,
                'amount' => $deposit->formattedAmount(),
                'method' => $deposit->paymentMethod?->name,
            ],
            url('/customer/deposits'),
            'View your deposits',
            'heroicon-o-clock',
        );

        $duplicate = $deposit->hasDuplicateTransactionId() ? ' ⚠ duplicate TrxID' : '';

        $this->notifications->toAdmins(
            'New deposit to review',
            "{$deposit->deposit_number} — {$deposit->formattedAmount()} via ".($deposit->paymentMethod?->name ?? 'unknown').$duplicate,
            url('/admin/deposits'),
            'heroicon-o-banknotes',
        );
    }

    public function handleDepositApproved(DepositApproved $event): void
    {
        $deposit = $event->deposit;

        $this->notifications->toCustomer(
            $deposit->user,
            'deposit_approved',
            [
                'deposit_number' => $deposit->deposit_number,
                'amount' => $deposit->formattedAmount(),
                'balance' => Money::format((int) $deposit->user->fresh()->balance),
            ],
            url('/customer/wallet'),
            'View your wallet',
            'heroicon-o-check-circle',
        );

        if ($this->smsSettings->on_deposit_approved && $deposit->user->phone) {
            $this->sms->send($deposit->user->phone, "Your deposit of {$deposit->formattedAmount()} has been added to your wallet.");
        }
    }

    public function handleDepositRejected(DepositRejected $event): void
    {
        $deposit = $event->deposit;

        $this->notifications->toCustomer(
            $deposit->user,
            'deposit_rejected',
            [
                'deposit_number' => $deposit->deposit_number,
                'amount' => $deposit->formattedAmount(),
                'reason' => (string) $deposit->reject_reason,
            ],
            url('/customer/deposits'),
            'View your deposits',
            'heroicon-o-x-circle',
        );
    }

    public function handleInvoiceCreated(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;

        $this->notifications->toCustomer(
            $invoice->user,
            'invoice_created',
            [
                'invoice_number' => $invoice->invoice_number,
                'product_name' => $invoice->product_name,
                'amount' => $invoice->formattedAmount(),
                'due' => $invoice->due_at?->toFormattedDateString() ?? 'no due date',
            ],
            url('/customer/invoices'),
            'Pay this invoice',
            'heroicon-o-document-text',
        );
    }

    public function handleInvoicePaid(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        $this->notifications->toCustomer(
            $invoice->user,
            'invoice_paid',
            [
                'invoice_number' => $invoice->invoice_number,
                'product_name' => $invoice->product_name,
                'amount' => $invoice->formattedAmount(),
            ],
            url('/customer/orders'),
            'View your order',
            'heroicon-o-check-circle',
        );

        $this->notifications->toAdmins(
            'Invoice paid',
            "{$invoice->invoice_number} — {$invoice->formattedAmount()}",
            url('/admin/custom-invoices'),
            'heroicon-o-check-circle',
        );
    }

    /**
     * A failed gateway payment is only interesting to staff when the amount did not match,
     * which is the one case that can mean tampering rather than a declined card.
     */
    public function handlePaymentFailed(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if (! str_contains((string) $payment->sp_message, 'mismatch')) {
            return;
        }

        $this->notifications->toAdmins(
            'Payment amount mismatch',
            "Payment #{$payment->getKey()}: {$payment->sp_message}",
            url('/admin/payments'),
            'heroicon-o-exclamation-triangle',
        );
    }
}
