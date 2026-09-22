<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketOpened;
use App\Events\TicketReplied;
use App\Events\UserRegistered;
use App\Events\WithdrawalProcessed;
use App\Events\WithdrawalRequested;
use App\Models\Admin;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Tickets, withdrawals and the welcome email. */
final class SendSupportNotifications implements ShouldQueue
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    public function handleTicketOpened(TicketOpened $event): void
    {
        $ticket = $event->ticket;

        $this->notifications->toAdmins(
            'New support ticket',
            "{$ticket->ticket_number} — {$ticket->subject} ({$ticket->priority->label()})",
            url('/admin/tickets'),
            'heroicon-o-lifebuoy',
        );
    }

    /**
     * A reply notifies the other side of the conversation, never the person who wrote it.
     */
    public function handleTicketReplied(TicketReplied $event): void
    {
        $reply = $event->reply;
        $ticket = $reply->ticket->loadMissing('user');

        if ($reply->author_type === Admin::class) {
            $this->notifications->toCustomer(
                $ticket->user,
                'ticket_replied',
                [
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'message' => strip_tags($reply->message),
                ],
                url('/customer/tickets'),
                'Read the reply',
                'heroicon-o-chat-bubble-left-right',
            );

            return;
        }

        $this->notifications->toAdmins(
            'Customer replied to a ticket',
            "{$ticket->ticket_number} — {$ticket->subject}",
            url('/admin/tickets'),
            'heroicon-o-chat-bubble-left-right',
        );
    }

    public function handleWithdrawalRequested(WithdrawalRequested $event): void
    {
        $request = $event->withdrawal;

        $this->notifications->toAdmins(
            'Withdrawal requested',
            "{$request->request_number} — {$request->formattedAmount()} via {$request->method}",
            url('/admin/withdrawal-requests'),
            'heroicon-o-arrow-up-tray',
        );
    }

    public function handleWithdrawalProcessed(WithdrawalProcessed $event): void
    {
        $request = $event->withdrawal;

        $this->notifications->toCustomer(
            $request->user,
            'withdrawal_processed',
            [
                'request_number' => $request->request_number,
                'amount' => $request->formattedAmount(),
                'status' => $request->status->label(),
                'note' => (string) $request->admin_note,
            ],
            url('/customer/withdrawals'),
            'View your withdrawals',
            'heroicon-o-banknotes',
        );
    }

    public function handleUserRegistered(UserRegistered $event): void
    {
        $this->notifications->toCustomer(
            $event->user,
            'welcome',
            ['referral_code' => $event->user->referral_code],
            url('/customer'),
            'Open your account',
            'heroicon-o-hand-raised',
        );
    }
}
