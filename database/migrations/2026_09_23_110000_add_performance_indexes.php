<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the three query shapes that were still doing full table scans.
 *
 * Each was confirmed with EXPLAIN rather than guessed at; everything else the application
 * runs was already covered, and an index nobody's query uses is write cost with no read
 * benefit.
 *
 *   orders (payment_status, paid_at)
 *       Every report screen, the revenue chart and the source breakdown filter on a paid
 *       status and a date window. EXPLAIN reported type=ALL, key=NULL — a scan of the whole
 *       orders table on each one.
 *
 *   orders (paid_at)
 *       "paid_at IS NOT NULL" appears on its own in the fulfilment queue and the invoice
 *       guard, where the composite above cannot be used because the status is not named.
 *
 *   tickets (last_reply_at)
 *       The support queue's default ordering. EXPLAIN reported type=ALL with a filesort.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['payment_status', 'paid_at'], 'orders_payment_status_paid_at_index');
            $table->index('paid_at', 'orders_paid_at_index');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->index('last_reply_at', 'tickets_last_reply_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_payment_status_paid_at_index');
            $table->dropIndex('orders_paid_at_index');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex('tickets_last_reply_at_index');
        });
    }
};
