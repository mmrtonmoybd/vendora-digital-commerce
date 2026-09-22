<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethodType: string
{
    /** Paid from the customer's own wallet balance. */
    case Wallet = 'wallet';
    /** Customer sends money by bKash/Nagad/Rocket and submits a TrxID for an admin to verify. */
    case Manual = 'manual';
    /** Redirect to an online gateway (shurjoPay) that we verify server-to-server. */
    case Gateway = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::Wallet => 'Wallet',
            self::Manual => 'Manual (mobile banking)',
            self::Gateway => 'Online gateway',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Wallet => 'gray',
            self::Manual => 'warning',
            self::Gateway => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Wallet => 'heroicon-o-wallet',
            self::Manual => 'heroicon-o-device-phone-mobile',
            self::Gateway => 'heroicon-o-credit-card',
        };
    }
}
