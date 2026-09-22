<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AccessFulfillmentService;
use Illuminate\Console\Command;

/** Flags fulfilment that has run past its target, and closes items the customer never confirmed. */
final class ProcessAccessQueue extends Command
{
    protected $signature = 'access:process';

    protected $description = 'Flag SLA breaches and auto-confirm access the customer never acknowledged';

    public function handle(AccessFulfillmentService $access): int
    {
        $breaches = $access->flagSlaBreaches();
        $confirmed = $access->autoConfirmStale();

        $this->info("Flagged {$breaches} SLA breach(es), auto-confirmed {$confirmed} item(s).");

        return self::SUCCESS;
    }
}
