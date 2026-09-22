<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events;
use App\Listeners\SendOrderNotifications;
use App\Listeners\SendPaymentNotifications;
use App\Listeners\SendSupportNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires every domain event to the people it concerns.
 *
 * Mapping is explicit rather than auto-discovered so the full list of consequences of, say,
 * an order being paid is readable in one place.
 */
final class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $listen = [
            Events\OrderPlaced::class => [[SendOrderNotifications::class, 'handleOrderPlaced']],
            Events\OrderPaid::class => [[SendOrderNotifications::class, 'handleOrderPaid']],
            Events\PaymentProofSubmitted::class => [[SendOrderNotifications::class, 'handleProofSubmitted']],
            Events\OrderCompleted::class => [[SendOrderNotifications::class, 'handleOrderCompleted']],
            Events\OrderCancelled::class => [[SendOrderNotifications::class, 'handleOrderCancelled']],
            Events\OrderRefunded::class => [[SendOrderNotifications::class, 'handleOrderRefunded']],

            Events\DepositSubmitted::class => [[SendPaymentNotifications::class, 'handleDepositSubmitted']],
            Events\DepositApproved::class => [[SendPaymentNotifications::class, 'handleDepositApproved']],
            Events\DepositRejected::class => [[SendPaymentNotifications::class, 'handleDepositRejected']],
            Events\InvoiceCreated::class => [[SendPaymentNotifications::class, 'handleInvoiceCreated']],
            Events\InvoicePaid::class => [[SendPaymentNotifications::class, 'handleInvoicePaid']],
            Events\PaymentFailed::class => [[SendPaymentNotifications::class, 'handlePaymentFailed']],

            Events\TicketOpened::class => [[SendSupportNotifications::class, 'handleTicketOpened']],
            Events\TicketReplied::class => [[SendSupportNotifications::class, 'handleTicketReplied']],
            Events\WithdrawalRequested::class => [[SendSupportNotifications::class, 'handleWithdrawalRequested']],
            Events\WithdrawalProcessed::class => [[SendSupportNotifications::class, 'handleWithdrawalProcessed']],
            Events\UserRegistered::class => [[SendSupportNotifications::class, 'handleUserRegistered']],
        ];

        foreach ($listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
