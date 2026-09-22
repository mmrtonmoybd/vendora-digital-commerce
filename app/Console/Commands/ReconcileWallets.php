<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Compares every stored balance against its ledger.
 *
 * A non-zero drift means something wrote to users.balance outside WalletService. That is
 * always a bug, so it is reported loudly rather than quietly corrected — though the balance
 * is corrected too, because a wrong balance is money the shop either owes or does not.
 */
final class ReconcileWallets extends Command
{
    protected $signature = 'wallet:reconcile {--dry-run : Report drift without correcting it}';

    protected $description = 'Check every wallet balance against its ledger';

    public function handle(WalletService $wallet, NotificationDispatcher $notifications): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $drifted = [];

        User::query()->chunkById(200, function ($users) use ($wallet, $dryRun, &$drifted): void {
            foreach ($users as $user) {
                $drift = $dryRun ? $wallet->drift($user) : $wallet->reconcile($user);

                if ($drift !== 0) {
                    $drifted[] = ['id' => $user->id, 'email' => $user->email, 'drift' => $drift];
                }
            }
        });

        if ($drifted === []) {
            $this->info('Every wallet matches its ledger.');

            return self::SUCCESS;
        }

        $this->table(['Customer', 'Email', 'Drift'], array_map(
            fn (array $row): array => [$row['id'], $row['email'], Money::format($row['drift'])],
            $drifted,
        ));

        Log::channel('wallet')->critical('wallet.reconcile.drift', ['accounts' => $drifted]);

        $notifications->toAdmins(
            'Wallet drift detected',
            count($drifted).' wallet(s) did not match the ledger. Check the wallet log.',
            url('/admin/customers'),
            'heroicon-o-exclamation-triangle',
        );

        return self::FAILURE;
    }
}
