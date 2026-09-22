<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\Money;
use RuntimeException;

final class InsufficientBalance extends RuntimeException
{
    public function __construct(public readonly int $balance, public readonly int $required)
    {
        parent::__construct(sprintf(
            'Wallet balance is not enough. Available %s, required %s.',
            Money::format($balance),
            Money::format($required),
        ));
    }
}
