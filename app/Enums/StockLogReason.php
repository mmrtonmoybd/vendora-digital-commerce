<?php

declare(strict_types=1);

namespace App\Enums;

enum StockLogReason: string
{
    case Order = 'order';
    case Refund = 'refund';
    case Cancel = 'cancel';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Reserved for order',
            self::Refund => 'Returned by refund',
            self::Cancel => 'Released by cancellation',
            self::Manual => 'Manual adjustment',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Order => 'danger',
            self::Refund, self::Cancel => 'success',
            self::Manual => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Order => 'heroicon-o-shopping-cart',
            self::Refund => 'heroicon-o-arrow-uturn-left',
            self::Cancel => 'heroicon-o-x-circle',
            self::Manual => 'heroicon-o-pencil-square',
        };
    }
}
