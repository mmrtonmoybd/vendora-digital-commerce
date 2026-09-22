<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case CustomerReply = 'customer_reply';
    case OnHold = 'on_hold';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Answered => 'Answered',
            self::CustomerReply => 'Customer replied',
            self::OnHold => 'On hold',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Answered => 'success',
            self::CustomerReply => 'danger',
            self::OnHold => 'gray',
            self::Closed => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-inbox-arrow-down',
            self::Answered => 'heroicon-o-chat-bubble-left-right',
            self::CustomerReply => 'heroicon-o-exclamation-circle',
            self::OnHold => 'heroicon-o-pause-circle',
            self::Closed => 'heroicon-o-lock-closed',
        };
    }

    /** Needs someone on the support desk to look at it. */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Open, self::CustomerReply], true);
    }
}
