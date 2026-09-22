<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponType: string
{
    /** `value` is a whole percentage, 1–100. */
    case Percent = 'percent';
    /** `value` is an amount in paisa. */
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }

    public function color(): string
    {
        return $this === self::Percent ? 'info' : 'success';
    }

    public function icon(): string
    {
        return $this === self::Percent ? 'heroicon-o-receipt-percent' : 'heroicon-o-banknotes';
    }
}
