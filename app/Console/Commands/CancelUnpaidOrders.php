<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Settings\CommerceSettings;
use Illuminate\Console\Command;

/**
 * Releases stock held by orders nobody paid for.
 *
 * Stock is reserved the moment an order is placed, which is what stops two people buying
 * the last licence. The cost of that is abandoned orders sitting on inventory, so they are
 * cancelled once the window has passed.
 */
final class CancelUnpaidOrders extends Command
{
    protected $signature = 'orders:cancel-unpaid';

    protected $description = 'Cancel unpaid orders past the window and release their stock';

    public function handle(OrderService $orders, CommerceSettings $settings): int
    {
        $cutoff = now()->subMinutes($settings->auto_cancel_unpaid_minutes);

        $stale = Order::query()
            ->where('status', OrderStatus::PendingPayment->value)
            ->where('created_at', '<', $cutoff)
            // A submitted receipt is waiting on staff, not on the customer. Cancelling it
            // would throw away a payment somebody has already made.
            ->where('payment_status', '!=', OrderPaymentStatus::Submitted->value)
            ->with('items')
            ->get();

        $cancelled = 0;

        foreach ($stale as $order) {
            try {
                $orders->cancel($order, 'Not paid within '.$settings->auto_cancel_unpaid_minutes.' minutes.');
                $cancelled++;
            } catch (\Throwable $e) {
                $this->error("Could not cancel {$order->order_number}: {$e->getMessage()}");
            }
        }

        $this->info("Cancelled {$cancelled} unpaid order(s).");

        return self::SUCCESS;
    }
}
