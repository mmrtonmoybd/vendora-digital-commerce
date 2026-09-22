<?php

declare(strict_types=1);

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::AwaitingPayment => 'Awaiting payment',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::AwaitingPayment => 'info',
            self::Completed => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::AwaitingPayment => 'heroicon-o-arrow-top-right-on-square',
            self::Completed => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
            self::Cancelled => 'heroicon-o-minus-circle',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::AwaitingPayment], true);
    }
}
