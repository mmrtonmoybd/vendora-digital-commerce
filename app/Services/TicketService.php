<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Events\TicketOpened;
use App\Events\TicketReplied;
use App\Exceptions\DomainException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TicketService
{
    /**
     * @param  array<int, string>  $attachments  paths on the private disk
     */
    public function open(User $user, ?Order $order, string $subject, string $message, array $attachments = [], string $priority = 'normal'): Ticket
    {
        // A customer may only attach one of their own orders; otherwise a ticket becomes a
        // way to read someone else's order details through the reply thread.
        if ($order !== null && $order->user_id !== $user->getKey()) {
            throw new DomainException('That order does not belong to you.');
        }

        $ticket = DB::transaction(function () use ($user, $order, $subject, $message, $attachments, $priority): Ticket {
            $ticket = Ticket::create([
                'ticket_number' => 'TK-'.now()->format('ym').'-'.Str::upper(Str::random(6)),
                'user_id' => $user->getKey(),
                'order_id' => $order?->getKey(),
                'subject' => $subject,
                'priority' => $priority,
                'status' => TicketStatus::Open,
                'last_reply_at' => now(),
            ]);

            $ticket->replies()->create([
                'author_type' => $user->getMorphClass(),
                'author_id' => $user->getKey(),
                'message' => $message,
                'attachments' => $attachments,
            ]);

            return $ticket;
        });

        TicketOpened::dispatch($ticket);

        return $ticket;
    }

    /**
     * @param  array<int, string>  $attachments
     */
    public function reply(Ticket $ticket, Admin|User $author, string $message, array $attachments = [], bool $internalNote = false): TicketReply
    {
        if ($internalNote && ! $author instanceof Admin) {
            throw new DomainException('Only staff can leave an internal note.');
        }

        if ($ticket->isClosed() && $author instanceof User) {
            throw new DomainException('This ticket is closed. Please open a new one.');
        }

        $reply = DB::transaction(function () use ($ticket, $author, $message, $attachments, $internalNote): TicketReply {
            $reply = $ticket->replies()->create([
                'author_type' => $author->getMorphClass(),
                'author_id' => $author->getKey(),
                'message' => $message,
                'attachments' => $attachments,
                'is_internal_note' => $internalNote,
            ]);

            // An internal note is staff talking to staff: it must not change the status the
            // customer sees, nor count as a first response.
            if ($internalNote) {
                return $reply;
            }

            $isStaff = $author instanceof Admin;

            $ticket->forceFill([
                'status' => $isStaff ? TicketStatus::Answered : TicketStatus::CustomerReply,
                'last_reply_at' => now(),
                'first_responded_at' => $isStaff ? ($ticket->first_responded_at ?? now()) : $ticket->first_responded_at,
                'assigned_admin_id' => $isStaff ? ($ticket->assigned_admin_id ?? $author->getKey()) : $ticket->assigned_admin_id,
            ])->save();

            return $reply;
        });

        if (! $internalNote) {
            TicketReplied::dispatch($reply);
        }

        return $reply;
    }

    public function close(Ticket $ticket, Model $actor): Ticket
    {
        $ticket->forceFill([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ])->save();

        return $ticket;
    }

    public function reopen(Ticket $ticket): Ticket
    {
        $ticket->forceFill([
            'status' => TicketStatus::CustomerReply,
            'closed_at' => null,
        ])->save();

        return $ticket;
    }

    public function assign(Ticket $ticket, Admin $admin): Ticket
    {
        $ticket->forceFill(['assigned_admin_id' => $admin->getKey()])->save();

        return $ticket;
    }

    public function setStatus(Ticket $ticket, TicketStatus $status): Ticket
    {
        $ticket->forceFill([
            'status' => $status,
            'closed_at' => $status === TicketStatus::Closed ? now() : null,
        ])->save();

        return $ticket;
    }
}
