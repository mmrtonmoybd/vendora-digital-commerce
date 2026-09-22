<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Initiated => 'gray',
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed, self::Expired => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Initiated => 'heroicon-o-play-circle',
            self::Pending => 'heroicon-o-clock',
            self::Paid => 'heroicon-o-check-circle',
            self::Failed => 'heroicon-o-x-circle',
            self::Cancelled => 'heroicon-o-minus-circle',
            self::Expired => 'heroicon-o-exclamation-triangle',
        };
    }

    /** Still worth asking the gateway about. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Initiated, self::Pending], true);
    }
}
