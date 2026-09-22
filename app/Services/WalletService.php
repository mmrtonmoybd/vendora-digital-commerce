<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletTxType;
use App\Exceptions\InsufficientBalance;
use App\Models\Admin;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only code in the system allowed to move a wallet balance.
 *
 * Two rules make the balance defensible: the user row is locked for the whole transaction,
 * so two concurrent debits cannot both read the same starting balance; and every call
 * carries an idempotency key, so a retried job, a double-clicked button or a replayed
 * gateway callback credits the customer exactly once.
 */
final class WalletService
{
    public function credit(
        User $user,
        int $amount,
        WalletTxType $type,
        ?Model $reference = null,
        ?string $idempotencyKey = null,
        ?string $description = null,
        ?Admin $admin = null,
    ): WalletTransaction {
        return $this->post($user, abs($amount), $type, $reference, $idempotencyKey, $description, $admin);
    }

    /** @throws InsufficientBalance */
    public function debit(
        User $user,
        int $amount,
        WalletTxType $type,
        ?Model $reference = null,
        ?string $idempotencyKey = null,
        ?string $description = null,
        ?Admin $admin = null,
    ): WalletTransaction {
        return $this->post($user, -abs($amount), $type, $reference, $idempotencyKey, $description, $admin);
    }

    /**
     * Post a signed amount to the ledger and move the balance to match.
     *
     * @param  int  $signedAmount  positive credits, negative debits
     */
    private function post(
        User $user,
        int $signedAmount,
        WalletTxType $type,
        ?Model $reference,
        ?string $idempotencyKey,
        ?string $description,
        ?Admin $admin,
    ): WalletTransaction {
        $key = $idempotencyKey ?: $this->generateKey($user, $type, $reference, $signedAmount);

        return DB::transaction(function () use ($user, $signedAmount, $type, $reference, $key, $description, $admin): WalletTransaction {
            // A duplicate key means this exact movement already happened. Returning the
            // original row keeps callers idempotent without them having to know.
            $existing = WalletTransaction::where('idempotency_key', $key)->first();

            if ($existing !== null) {
                return $existing;
            }

            /** @var User $locked */
            $locked = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $before = (int) $locked->balance;
            $after = $before + $signedAmount;

            if ($after < 0) {
                throw new InsufficientBalance($before, abs($signedAmount));
            }

            $transaction = WalletTransaction::create([
                'user_id' => $locked->getKey(),
                'type' => $type,
                'amount' => $signedAmount,
                'balance_before' => $before,
                'balance_after' => $after,
                'idempotency_key' => $key,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'admin_id' => $admin?->getKey(),
            ]);

            $locked->forceFill(['balance' => $after])->save();
            $user->setAttribute('balance', $after);

            Log::channel('wallet')->info('wallet.posted', [
                'user_id' => $locked->getKey(),
                'type' => $type->value,
                'amount' => $signedAmount,
                'balance_after' => $after,
                'idempotency_key' => $key,
            ]);

            return $transaction;
        }, 3);
    }

    /**
     * Compare the stored balance against the ledger.
     *
     * A non-zero result means something wrote to the column outside this service, and is
     * always a bug worth an alert rather than a silent correction.
     */
    public function drift(User $user): int
    {
        $ledger = (int) WalletTransaction::where('user_id', $user->getKey())->sum('amount');

        return (int) $user->balance - $ledger;
    }

    /** Recompute the balance from the ledger. Used by the reconcile command. */
    public function reconcile(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            /** @var User $locked */
            $locked = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $ledger = (int) WalletTransaction::where('user_id', $locked->getKey())->sum('amount');
            $drift = (int) $locked->balance - $ledger;

            if ($drift !== 0) {
                Log::channel('wallet')->warning('wallet.drift', [
                    'user_id' => $locked->getKey(),
                    'stored' => $locked->balance,
                    'ledger' => $ledger,
                    'drift' => $drift,
                ]);

                $locked->forceFill(['balance' => $ledger])->save();
            }

            return $drift;
        });
    }

    private function generateKey(User $user, WalletTxType $type, ?Model $reference, int $amount): string
    {
        return implode(':', [
            'wallet',
            $user->getKey(),
            $type->value,
            $reference?->getMorphClass() ?? 'none',
            $reference?->getKey() ?? '0',
            $amount,
            (string) now()->getTimestampMs(),
        ]);
    }
}
