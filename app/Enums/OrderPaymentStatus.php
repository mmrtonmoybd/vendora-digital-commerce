<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderPaymentStatus: string
{
    case Unpaid = 'unpaid';
    /** Manual proof submitted, waiting for an admin to verify it. */
    case Submitted = 'submitted';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Submitted => 'Proof submitted',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'danger',
            self::Submitted => 'warning',
            self::Paid => 'success',
            self::Refunded => 'gray',
            self::PartiallyRefunded => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Unpaid => 'heroicon-o-exclamation-circle',
            self::Submitted => 'heroicon-o-paper-airplane',
            self::Paid => 'heroicon-o-check-badge',
            self::Refunded, self::PartiallyRefunded => 'heroicon-o-arrow-uturn-left',
        };
    }
}
