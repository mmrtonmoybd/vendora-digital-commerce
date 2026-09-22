<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ShurjopayService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ask shurjoPay about one payment.
 *
 * Unique per gateway order id: the return URL, the IPN and the reconcile command can all
 * ask about the same payment within seconds of each other, and there is no reason to make
 * three identical calls.
 */
final class VerifyShurjopayPayment implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 120;

    public function __construct(
        public readonly string $spOrderId,
        public readonly string $source = 'ipn',
    ) {}

    public function uniqueId(): string
    {
        return $this->spOrderId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(ShurjopayService $shurjopay): void
    {
        $shurjopay->verifyAndApply($this->spOrderId, $this->source);
    }
}
