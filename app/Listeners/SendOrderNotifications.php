<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Events\OrderRefunded;
use App\Events\PaymentProofSubmitted;
use App\Models\Order;
use App\Services\AccessFulfillmentService;
use App\Services\NotificationDispatcher;
use App\Services\SmsService;
use App\Settings\SmsSettings;
use App\Support\Money;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\URL;

/**
 * Everything a customer and the back office hear about an order.
 *
 * Queued: a slow SMTP handshake must never sit between a customer and their confirmation
 * page, and a mail server being down must not roll back a paid order.
 */
final class SendOrderNotifications implements ShouldQueue
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly AccessFulfillmentService $access,
        private readonly SmsService $sms,
        private readonly SmsSettings $smsSettings,
    ) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order;

        $this->customerEmail($order, 'order_placed', 'Order received', 'We have your order.');

        $this->notifications->toAdmins(
            'New order placed',
            "{$order->order_number} — {$order->formattedTotal()} via ".($order->paymentMethod?->name ?? 'unknown'),
            url('/admin/orders'),
            'heroicon-o-shopping-bag',
        );
    }

    public function handleOrderPaid(OrderPaid $event): void
    {
        $order = $event->order->loadMissing('items', 'user');

        $this->customerEmail($order, 'order_paid_awaiting_access', 'Payment received', 'Your payment has been confirmed.');

        // Paid is what puts the order into the fulfilment queue.
        $this->access->onOrderPaid($order);
    }

    public function handleProofSubmitted(PaymentProofSubmitted $event): void
    {
        $order = $event->order;

        $this->notifications->toAdmins(
            'Payment proof submitted',
            "{$order->order_number} — TrxID {$order->manual_transaction_id} for {$order->formattedTotal()}",
            url('/admin/orders'),
            'heroicon-o-document-check',
        );

        $this->customerEmail($order, 'order_proof_submitted', 'We received your payment details', 'We are verifying your payment.');
    }

    public function handleOrderCompleted(OrderCompleted $event): void
    {
        $order = $event->order->loadMissing('items', 'user');

        $this->customerEmail($order, 'order_completed', 'Your order is complete', 'Your order has been completed.');

        if ($this->smsSettings->on_order_completed && $order->user?->phone) {
            $this->sms->send($order->user->phone, "Your order {$order->order_number} is complete.");
        }
    }

    public function handleOrderCancelled(OrderCancelled $event): void
    {
        $this->customerEmail($event->order, 'order_cancelled', 'Order cancelled', 'Your order has been cancelled.');
    }

    public function handleOrderRefunded(OrderRefunded $event): void
    {
        $order = $event->order;

        $this->customerEmail($order, 'order_refunded', 'Refund issued', 'A refund has been added to your wallet.', [
            'amount' => Money::format($order->refunded_amount),
        ]);
    }

    /** @param array<string, string|int|null> $extra */
    private function customerEmail(Order $order, string $templateKey, string $fallbackTitle, string $fallbackBody, array $extra = []): void
    {
        $variables = [
            'order_number' => $order->order_number,
            'amount' => $order->formattedTotal(),
            'delivery_email' => $order->delivery_email,
            'items' => $order->itemsSummary(),
            'delivery_note' => strip_tags((string) $order->delivery_note),
            'url' => route('order.pay', $order),
            ...$extra,
        ];

        if ($order->user !== null) {
            $this->notifications->toCustomer(
                $order->user,
                $templateKey,
                $variables,
                route('order.pay', $order),
                'View your order',
            );

            return;
        }

        // Guests have no account to notify, so the order page link is signed and emailed.
        $this->notifications->guestEmail(
            $order->customerEmail(),
            $templateKey,
            $variables + ['name' => 'there'],
            URL::signedRoute('order.pay', ['order' => $order->order_number]),
            'View your order',
            $fallbackTitle,
            $fallbackBody,
        );
    }
}
