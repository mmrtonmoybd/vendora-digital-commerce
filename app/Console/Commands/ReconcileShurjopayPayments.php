<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\ShurjopayService;
use Illuminate\Console\Command;

/**
 * Asks shurjoPay about payments we never got a clean answer for.
 *
 * The customer's browser can close between paying and returning, and an IPN can be lost.
 * This is the third net under a payment: anything still open after a few minutes is asked
 * about directly, and anything still unresolved after two days is written off as expired so
 * the order can release its stock.
 */
final class ReconcileShurjopayPayments extends Command
{
    protected $signature = 'shurjopay:reconcile';

    protected $description = 'Re-verify gateway payments that never reached a final state';

    public function handle(ShurjopayService $shurjopay): int
    {
        $checked = 0;
        $settled = 0;

        Payment::query()
            ->open()
            ->whereNotNull('sp_order_id')
            // Give the normal return/IPN path a few minutes before second-guessing it.
            ->where('created_at', '<', now()->subMinutes(3))
            ->where('created_at', '>', now()->subHours(48))
            ->where(function ($query): void {
                $query->whereNull('last_checked_at')
                    ->orWhere('last_checked_at', '<', now()->subMinutes(10));
            })
            ->chunkById(100, function ($payments) use ($shurjopay, &$checked, &$settled): void {
                foreach ($payments as $payment) {
                    $result = $shurjopay->verifyAndApply((string) $payment->sp_order_id, 'reconcile');
                    $checked++;

                    if ($result?->isPaid()) {
                        $settled++;
                    }
                }
            });

        // Past two days the gateway has nothing useful left to say.
        $expired = Payment::query()
            ->open()
            ->where('created_at', '<=', now()->subHours(48))
            ->update(['status' => PaymentStatus::Expired->value]);

        $this->info("Checked {$checked}, settled {$settled}, expired {$expired}.");

        return self::SUCCESS;
    }
}
