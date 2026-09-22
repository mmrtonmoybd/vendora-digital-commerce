<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\WalletTxType;
use App\Events\DepositApproved;
use App\Events\DepositRejected;
use App\Events\DepositSubmitted;
use App\Exceptions\DomainException;
use App\Models\Admin;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Topping up the wallet, by mobile banking receipt or by the online gateway.
 *
 * The wallet is credited with the deposit amount, never with the amount plus the fee: the
 * fee is what the business keeps for handling it.
 */
final class DepositService
{
    public function __construct(private readonly WalletService $wallet) {}

    public function createManual(
        User $user,
        PaymentMethod $method,
        int $amount,
        string $senderNumber,
        string $transactionId,
        ?string $screenshotPath = null,
    ): Deposit {
        if (! $method->isManual()) {
            throw new DomainException('That payment method does not take a receipt.');
        }

        $this->assertMethodAccepts($method, $amount);

        if ($this->transactionIdTaken($method, $transactionId)) {
            throw new DomainException('That transaction ID has already been used.');
        }

        $fee = $method->feeFor($amount);

        $deposit = Deposit::create([
            'deposit_number' => $this->nextNumber(),
            'user_id' => $user->getKey(),
            'payment_method_id' => $method->getKey(),
            'amount' => $amount,
            'fee' => $fee,
            'payable' => $amount + $fee,
            'sender_number' => $senderNumber,
            'transaction_id' => $transactionId,
            'screenshot' => $screenshotPath,
            'status' => DepositStatus::Pending,
        ]);

        DepositSubmitted::dispatch($deposit);

        return $deposit;
    }

    /** Gateway deposits sit in awaiting_payment until shurjoPay verification says otherwise. */
    public function createGateway(User $user, PaymentMethod $method, int $amount): Deposit
    {
        if (! $method->isGateway()) {
            throw new DomainException('That payment method is not an online gateway.');
        }

        $this->assertMethodAccepts($method, $amount);

        $fee = $method->feeFor($amount);

        return Deposit::create([
            'deposit_number' => $this->nextNumber(),
            'user_id' => $user->getKey(),
            'payment_method_id' => $method->getKey(),
            'amount' => $amount,
            'fee' => $fee,
            'payable' => $amount + $fee,
            'status' => DepositStatus::AwaitingPayment,
        ]);
    }

    /** Staff verified the receipt. Idempotent, so a double-click cannot credit twice. */
    public function approve(Deposit $deposit, Admin $admin, ?string $note = null): Deposit
    {
        $deposit = DB::transaction(function () use ($deposit, $admin, $note): Deposit {
            /** @var Deposit $locked */
            $locked = Deposit::whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === DepositStatus::Completed) {
                return $locked;
            }

            if (! $locked->status->isOpen()) {
                throw new DomainException('This deposit has already been closed.');
            }

            $this->wallet->credit(
                $locked->user,
                $locked->amount,
                WalletTxType::Deposit,
                $locked,
                "deposit:{$locked->getKey()}",
                "Deposit {$locked->deposit_number}",
                $admin,
            );

            $locked->forceFill([
                'status' => DepositStatus::Completed,
                'admin_note' => $note,
                'processed_by' => $admin->getKey(),
                'processed_at' => now(),
            ])->save();

            return $locked;
        }, 3);

        DepositApproved::dispatch($deposit);

        return $deposit;
    }

    /**
     * Credit a deposit the gateway could not confirm on its own.
     *
     * The everyday case is a gateway timeout after the customer was already charged: the
     * bank statement shows the money, our verification call does not. Staff can settle it,
     * with a mandatory reason that lands in the activity log.
     */
    public function forceApprove(Deposit $deposit, Admin $admin, string $reason): Deposit
    {
        if (trim($reason) === '') {
            throw new DomainException('A reason is required when approving a deposit manually.');
        }

        if ($deposit->status === DepositStatus::Completed) {
            throw new DomainException('This deposit is already completed.');
        }

        $deposit = DB::transaction(function () use ($deposit, $admin, $reason): Deposit {
            /** @var Deposit $locked */
            $locked = Deposit::whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            $this->wallet->credit(
                $locked->user,
                $locked->amount,
                WalletTxType::Deposit,
                $locked,
                "deposit:{$locked->getKey()}",
                "Deposit {$locked->deposit_number} (manually approved)",
                $admin,
            );

            $locked->forceFill([
                'status' => DepositStatus::Completed,
                'admin_note' => "Manually approved by {$admin->name}: {$reason}",
                'processed_by' => $admin->getKey(),
                'processed_at' => now(),
            ])->save();

            return $locked;
        }, 3);

        activity('deposit')
            ->performedOn($deposit)
            ->causedBy($admin)
            ->withProperties(['reason' => $reason, 'amount' => $deposit->amount])
            ->log('deposit.manually_approved');

        DepositApproved::dispatch($deposit);

        return $deposit;
    }

    public function reject(Deposit $deposit, Admin $admin, string $reason): Deposit
    {
        if ($deposit->status === DepositStatus::Completed) {
            throw new DomainException('A completed deposit cannot be rejected. Issue a wallet debit instead.');
        }

        $deposit->forceFill([
            'status' => DepositStatus::Rejected,
            'reject_reason' => $reason,
            'processed_by' => $admin->getKey(),
            'processed_at' => now(),
        ])->save();

        DepositRejected::dispatch($deposit);

        return $deposit;
    }

    /** Called by the gateway verification path once a payment is confirmed. */
    public function markGatewayPaid(Deposit $deposit): Deposit
    {
        $deposit = DB::transaction(function () use ($deposit): Deposit {
            /** @var Deposit $locked */
            $locked = Deposit::whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === DepositStatus::Completed) {
                return $locked;
            }

            $this->wallet->credit(
                $locked->user,
                $locked->amount,
                WalletTxType::Deposit,
                $locked,
                "deposit:{$locked->getKey()}",
                "Deposit {$locked->deposit_number}",
            );

            $locked->forceFill([
                'status' => DepositStatus::Completed,
                'processed_at' => now(),
            ])->save();

            return $locked;
        }, 3);

        DepositApproved::dispatch($deposit);

        return $deposit;
    }

    private function assertMethodAccepts(PaymentMethod $method, int $amount): void
    {
        if (! $method->is_active || ! $method->available_for_deposits) {
            throw new DomainException('That payment method is not available for deposits.');
        }

        if ($amount <= 0) {
            throw new DomainException('Enter an amount to deposit.');
        }

        if (! $method->acceptsAmount($amount)) {
            $min = Money::format($method->min_amount);
            $max = $method->max_amount ? ' and at most '.Money::format($method->max_amount) : '';

            throw new DomainException("Deposits through {$method->name} must be at least {$min}{$max}.");
        }
    }

    private function transactionIdTaken(PaymentMethod $method, string $transactionId): bool
    {
        return Deposit::where('payment_method_id', $method->getKey())
            ->where('transaction_id', $transactionId)
            ->exists();
    }

    private function nextNumber(): string
    {
        return 'DP-'.now()->format('ym').'-'.Str::upper(Str::random(6));
    }
}
