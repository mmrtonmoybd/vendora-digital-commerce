<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WithdrawalProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly WithdrawalRequest $withdrawal) {}
}
