<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Paid => 'success',
            self::Cancelled => 'gray',
            self::Expired => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Unpaid => 'heroicon-o-clock',
            self::Paid => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-minus-circle',
            self::Expired => 'heroicon-o-exclamation-triangle',
        };
    }
}
