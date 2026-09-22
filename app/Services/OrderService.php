<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\StockLogReason;
use App\Enums\WalletTxType;
use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\PaymentProofSubmitted;
use App\Exceptions\DomainException;
use App\Exceptions\InvalidTransition;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Settings\CommerceSettings;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every change of an order's state goes through here.
 *
 * Nothing else may write `status` or `payment_status`: the allowed-transitions map on the
 * enum is the single definition of what an order can do next, and every move leaves a
 * history row saying who did it.
 */
final class OrderService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly StockService $stock,
        private readonly AffiliateService $affiliate,
        private readonly CommerceSettings $settings,
    ) {}

    /** Customer sent money by mobile banking and gave us the receipt details. */
    public function submitManualProof(Order $order, string $senderNumber, string $transactionId, ?string $screenshotPath = null): Order
    {
        if ($order->isPaid()) {
            throw new DomainException('This order is already paid.');
        }

        $duplicate = Order::where('payment_method_id', $order->payment_method_id)
            ->where('manual_transaction_id', $transactionId)
            ->whereKeyNot($order->getKey())
            ->exists();

        if ($duplicate) {
            throw new DomainException('That transaction ID has already been submitted for another order.');
        }

        DB::transaction(function () use ($order, $senderNumber, $transactionId, $screenshotPath): void {
            $order->forceFill([
                'manual_sender_number' => $senderNumber,
                'manual_transaction_id' => $transactionId,
                'manual_screenshot' => $screenshotPath ?: $order->manual_screenshot,
                'payment_status' => OrderPaymentStatus::Submitted,
            ])->save();

            $this->history($order, $order->status, $order->status, 'Payment proof submitted.', $order->user);
        });

        PaymentProofSubmitted::dispatch($order->fresh());

        return $order->refresh();
    }

    /**
     * Mark an order paid. Idempotent: a return URL, an IPN and the reconcile job may all
     * arrive for the same payment, and only the first one does any work.
     *
     * @param  string  $source  gateway | wallet | manual | admin
     */
    public function markPaid(Order $order, string $source = 'gateway', ?Admin $admin = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $source, $admin, $note): Order {
            /** @var Order $locked */
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return $locked;
            }

            $from = $locked->status;
            $to = $from === OrderStatus::PendingPayment ? OrderStatus::Pending : $from;

            $locked->forceFill([
                'payment_status' => OrderPaymentStatus::Paid,
                'status' => $to,
                'paid_at' => now(),
                'processed_by' => $admin?->getKey() ?? $locked->processed_by,
            ])->save();

            $this->history($locked, $from, $to, $note ?? "Payment confirmed ({$source}).", $admin);

            return $locked;
        }, 3);
    }

    /**
     * Staff confirms a payment the system could not confirm itself.
     *
     * Needed for real cases: the gateway timed out after the customer was charged, a manual
     * receipt arrived by a channel other than the form, or a dispute was settled in the
     * customer's favour. The reason is mandatory and ends up in the order history.
     */
    public function markPaidByAdmin(Order $order, Admin $admin, string $reason): Order
    {
        if ($order->isPaid()) {
            throw new DomainException('This order is already paid.');
        }

        if (trim($reason) === '') {
            throw new DomainException('A reason is required when approving a payment manually.');
        }

        $order = $this->markPaid($order, 'admin', $admin, "Manually approved by {$admin->name}: {$reason}");

        activity('order')
            ->performedOn($order)
            ->causedBy($admin)
            ->withProperties(['reason' => $reason, 'amount' => $order->total])
            ->log('payment.manually_approved');

        OrderPaid::dispatch($order);

        return $order;
    }

    /** Staff rejects a manual receipt; the order goes back to unpaid so the customer can retry. */
    public function rejectManualProof(Order $order, Admin $admin, string $reason): Order
    {
        if ($order->payment_status !== OrderPaymentStatus::Submitted) {
            throw new DomainException('There is no payment proof waiting on this order.');
        }

        DB::transaction(function () use ($order, $admin, $reason): void {
            $order->forceFill([
                'payment_status' => OrderPaymentStatus::Unpaid,
                'manual_transaction_id' => null,
            ])->save();

            $this->history($order, $order->status, $order->status, "Payment proof rejected: {$reason}", $admin);
        });

        return $order->refresh();
    }

    public function startProcessing(Order $order, ?Admin $admin = null): Order
    {
        return $this->transition($order, OrderStatus::Processing, 'Fulfilment started.', $admin);
    }

    /** Fulfilment is done: the delivery note is what the customer actually receives. */
    public function complete(Order $order, ?string $deliveryNote = null, ?Admin $admin = null): Order
    {
        $order = DB::transaction(function () use ($order, $deliveryNote, $admin): Order {
            if (! $order->isPaid()) {
                throw new DomainException('An unpaid order cannot be completed.');
            }

            $order = $this->transition($order, OrderStatus::Completed, 'Order completed.', $admin, false);

            $order->forceFill([
                'delivery_note' => $deliveryNote ?: $order->delivery_note,
                'completed_at' => now(),
                'processed_by' => $admin?->getKey() ?? $order->processed_by,
            ])->save();

            return $order;
        });

        $this->affiliate->onOrderCompleted($order);

        OrderCompleted::dispatch($order);

        return $order;
    }

    /**
     * Cancel an order and put the stock back.
     *
     * A paid order that is cancelled is refunded to the wallet in the same transaction: the
     * customer must never end up with neither product nor money.
     */
    public function cancel(Order $order, string $reason, Admin|User|null $actor = null): Order
    {
        $order = DB::transaction(function () use ($order, $reason, $actor): Order {
            /** @var Order $locked */
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status->isFinal()) {
                throw new DomainException('This order can no longer be cancelled.');
            }

            $this->releaseStock($locked, StockLogReason::Cancel);

            if ($locked->isPaid() && $locked->user !== null) {
                $refundable = $locked->refundableAmount();

                if ($refundable > 0) {
                    $this->wallet->credit(
                        $locked->user,
                        $refundable,
                        WalletTxType::OrderRefund,
                        $locked,
                        "order-cancel-refund:{$locked->getKey()}",
                        "Refund for cancelled order {$locked->order_number}",
                        $actor instanceof Admin ? $actor : null,
                    );

                    $locked->forceFill([
                        'refunded_amount' => $locked->refunded_amount + $refundable,
                        'payment_status' => OrderPaymentStatus::Refunded,
                    ])->save();
                }
            }

            $locked->forceFill([
                'cancelled_at' => now(),
                'refund_reason' => $reason,
            ])->save();

            return $this->transition($locked, OrderStatus::Cancelled, "Cancelled: {$reason}", $actor);
        });

        $this->affiliate->reverseForOrder($order, 'Order cancelled');

        OrderCancelled::dispatch($order);

        return $order;
    }

    /**
     * Refund money to the customer's wallet, in full or in part.
     *
     * A full refund also reverses the affiliate commission and, when configured, puts the
     * stock back. A partial refund leaves the order otherwise intact.
     */
    public function refund(Order $order, ?int $amount, string $reason, Admin $admin): Order
    {
        $order = DB::transaction(function () use ($order, $amount, $reason, $admin): Order {
            /** @var Order $locked */
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPaid()) {
                throw new DomainException('Only a paid order can be refunded.');
            }

            $refundable = $locked->refundableAmount();
            $value = $amount === null ? $refundable : min($amount, $refundable);

            if ($value <= 0) {
                throw new DomainException('There is nothing left to refund on this order.');
            }

            if ($locked->user === null) {
                throw new DomainException('A guest order has no wallet to refund to. Refund it through the payment channel instead.');
            }

            $this->wallet->credit(
                $locked->user,
                $value,
                WalletTxType::OrderRefund,
                $locked,
                "order-refund:{$locked->getKey()}:{$locked->refunded_amount}",
                "Refund for order {$locked->order_number}",
                $admin,
            );

            $refundedTotal = $locked->refunded_amount + $value;
            $isFull = $refundedTotal >= $locked->total;

            $locked->forceFill([
                'refunded_amount' => $refundedTotal,
                'refund_reason' => $reason,
                'payment_status' => $isFull ? OrderPaymentStatus::Refunded : OrderPaymentStatus::PartiallyRefunded,
            ])->save();

            if ($isFull) {
                if ($this->settings->restore_stock_on_refund) {
                    $this->releaseStock($locked, StockLogReason::Refund);
                }

                $this->transition($locked, OrderStatus::Refunded, "Refunded in full: {$reason}", $admin, false);
            } else {
                $this->history($locked, $locked->status, $locked->status, 'Partial refund '.Money::format($value).": {$reason}", $admin);
            }

            return $locked;
        });

        if ($order->refunded_amount >= $order->total) {
            $this->affiliate->reverseForOrder($order, 'Order refunded');
        }

        OrderRefunded::dispatch($order, $order->refunded_amount);

        return $order->refresh();
    }

    /**
     * Move an order to a new status, checking the transition is legal first.
     *
     * @param  bool  $validate  false only when the caller has already proven the move is legal
     */
    public function transition(Order $order, OrderStatus $to, string $note, Admin|User|null $actor = null, bool $validate = true): Order
    {
        $from = $order->status;

        if ($from === $to) {
            return $order;
        }

        if ($validate && ! $from->canTransitionTo($to)) {
            throw new InvalidTransition($from, $to);
        }

        $order->forceFill(['status' => $to])->save();
        $this->history($order, $from, $to, $note, $actor);

        return $order;
    }

    private function history(Order $order, ?OrderStatus $from, OrderStatus $to, string $note, ?Model $actor): void
    {
        $order->histories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
        ]);
    }

    /** Put reserved stock back on the shelf. */
    private function releaseStock(Order $order, StockLogReason $reason): void
    {
        foreach ($order->items as $item) {
            $product = $item->product_id ? Product::find($item->product_id) : null;

            if ($product === null) {
                continue;
            }

            $this->stock->adjust($product, $item->quantity, $reason, $order);
            $product->decrement('total_sold', min($item->quantity, (int) $product->total_sold));
        }
    }

    /** @return Collection<int, OrderItem> */
    public function itemsFor(Order $order): Collection
    {
        return $order->items()->with('product')->get();
    }
}
