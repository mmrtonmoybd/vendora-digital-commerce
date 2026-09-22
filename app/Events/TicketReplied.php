<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TicketReply;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketReplied
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly TicketReply $reply) {}
}
