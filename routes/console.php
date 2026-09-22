<?php

declare(strict_types=1);

use App\Console\Commands\CancelUnpaidOrders;
use App\Console\Commands\ProcessAccessQueue;
use App\Console\Commands\ReconcileShurjopayPayments;
use App\Console\Commands\ReconcileWallets;
use Illuminate\Support\Facades\Schedule;

/*
 | The scheduler. One cron entry runs this:
 |   * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
 |
 | Everything that touches money uses withoutOverlapping: a second copy of the reconcile
 | job running while the first is mid-transaction is a way to double-credit somebody.
 */

Schedule::command(ReconcileShurjopayPayments::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(CancelUnpaidOrders::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command(ProcessAccessQueue::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command(ReconcileWallets::class)
    ->dailyAt('03:00')
    ->withoutOverlapping()
    // A drift report nobody reads is not a report.
    ->emailOutputOnFailure(config('mail.from.address'));

Schedule::command('sitemap:refresh')->dailyAt('04:00');

Schedule::command('backup:clean')->dailyAt('04:30')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('05:00')->withoutOverlapping();

Schedule::command('activitylog:clean')->weekly();
Schedule::command('model:prune')->daily();

// Failed jobs are worth keeping long enough to investigate, not forever.
Schedule::command('queue:prune-failed --hours=336')->weekly();
