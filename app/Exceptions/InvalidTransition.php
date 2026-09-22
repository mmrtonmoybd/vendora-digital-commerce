<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\OrderStatus;

final class InvalidTransition extends DomainException
{
    public function __construct(OrderStatus $from, OrderStatus $to)
    {
        parent::__construct("An order cannot move from {$from->label()} to {$to->label()}.");
    }
}
