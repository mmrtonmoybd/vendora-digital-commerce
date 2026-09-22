<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\WalletTxType;
use App\Events\InvoiceCreated;
use App\Events\InvoicePaid;
use App\Exceptions\DomainException;
use App\Models\Admin;
use App\Models\CustomInvoice;
use App\Models\Order;
use App\Models\User;
use App\Settings\CommerceSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A one-off bill for work that is not in the catalogue.
 *
 * Paying one creates a normal order, so staff fulfil it through exactly the same queue and
 * the customer sees it in the same order list.
 */
final class InvoiceService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly CommerceSettings $settings,
    ) {}

    public function create(
        User $user,
        string $productName,
        int $amount,
        ?string $description,
        ?string $note,
        ?Carbon $dueAt,
        Admin $admin,
    ): CustomInvoice {
        if ($amount <= 0) {
            throw new DomainException('An invoice needs an amount above zero.');
        }

        $invoice = CustomInvoice::create([
            'invoice_number' => 'INV-'.now()->format('ym').'-'.Str::upper(Str::random(6)),
            'user_id' => $user->getKey(),
            'product_name' => $productName,
            'description' => $description,
            'amount' => $amount,
            'note' => $note,
            'status' => InvoiceStatus::Unpaid,
            'due_at' => $dueAt,
            'created_by' => $admin->getKey(),
        ]);

        InvoiceCreated::dispatch($invoice);

        return $invoice;
    }

    public function payWithWallet(CustomInvoice $invoice): CustomInvoice
    {
        if (! $invoice->isPayable()) {
            throw new DomainException('This invoice is no longer payable.');
        }

        $invoice = DB::transaction(function () use ($invoice): CustomInvoice {
            /** @var CustomInvoice $locked */
            $locked = CustomInvoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPayable()) {
                return $locked;
            }

            $this->wallet->debit(
                $locked->user,
                $locked->amount,
                WalletTxType::InvoicePayment,
                $locked,
                "invoice:{$locked->getKey()}",
                "Payment for invoice {$locked->invoice_number}",
            );

            return $this->settle($locked, 'wallet');
        }, 3);

        InvoicePaid::dispatch($invoice);

        return $invoice;
    }

    /** Called by the gateway verification path. Idempotent. */
    public function markPaid(CustomInvoice $invoice, string $paidVia = 'gateway'): CustomInvoice
    {
        $invoice = DB::transaction(function () use ($invoice, $paidVia): CustomInvoice {
            /** @var CustomInvoice $locked */
            $locked = CustomInvoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPayable()) {
                return $locked;
            }

            return $this->settle($locked, $paidVia);
        }, 3);

        InvoicePaid::dispatch($invoice);

        return $invoice;
    }

    /** Staff settles an invoice paid through a channel the system cannot see. */
    public function markPaidByAdmin(CustomInvoice $invoice, Admin $admin, string $reason): CustomInvoice
    {
        if (! $invoice->isPayable()) {
            throw new DomainException('This invoice is no longer payable.');
        }

        if (trim($reason) === '') {
            throw new DomainException('A reason is required when settling an invoice manually.');
        }

        $invoice = $this->markPaid($invoice, 'admin');

        activity('invoice')
            ->performedOn($invoice)
            ->causedBy($admin)
            ->withProperties(['reason' => $reason, 'amount' => $invoice->amount])
            ->log('invoice.manually_settled');

        return $invoice;
    }

    public function cancel(CustomInvoice $invoice, Admin $admin, ?string $reason = null): CustomInvoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw new DomainException('A paid invoice cannot be cancelled. Refund the linked order instead.');
        }

        $invoice->forceFill([
            'status' => InvoiceStatus::Cancelled,
            'note' => $reason ?: $invoice->note,
        ])->save();

        return $invoice;
    }

    /** Mark paid and open the fulfilment order that staff will work from. */
    private function settle(CustomInvoice $invoice, string $paidVia): CustomInvoice
    {
        $order = Order::create([
            'order_number' => $this->nextOrderNumber(),
            'user_id' => $invoice->user_id,
            'delivery_email' => $invoice->user->email,
            'subtotal' => $invoice->amount,
            'discount' => 0,
            'fee' => 0,
            'total' => $invoice->amount,
            'payment_status' => OrderPaymentStatus::Paid,
            'status' => OrderStatus::Pending,
            'paid_at' => now(),
            'customer_note' => $invoice->note,
        ]);

        $order->items()->create([
            'product_id' => null,
            'product_snapshot' => [
                'name' => $invoice->product_name,
                'slug' => null,
                'short' => $invoice->description,
                'category_name' => 'Custom invoice',
                'price' => $invoice->amount,
                'regular_price' => null,
            ],
            'unit_price' => $invoice->amount,
            'quantity' => 1,
            'line_total' => $invoice->amount,
            'delivery_email' => $invoice->user->email,
        ]);

        $order->histories()->create([
            'from_status' => null,
            'to_status' => OrderStatus::Pending,
            'note' => "Created from invoice {$invoice->invoice_number} ({$paidVia}).",
        ]);

        $invoice->forceFill([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
            'paid_via' => $paidVia,
            'order_id' => $order->getKey(),
        ])->save();

        return $invoice;
    }

    private function nextOrderNumber(): string
    {
        $stem = $this->settings->order_prefix.'-'.now()->format('ym').'-';

        $last = Order::where('order_number', 'like', $stem.'%')->orderByDesc('id')->value('order_number');
        $sequence = $last ? ((int) substr((string) $last, -6)) + 1 : 1;

        return $stem.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
