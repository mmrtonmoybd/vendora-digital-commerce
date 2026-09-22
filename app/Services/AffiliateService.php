<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\WalletTxType;
use App\Enums\WithdrawalStatus;
use App\Events\WithdrawalProcessed;
use App\Events\WithdrawalRequested;
use App\Exceptions\DomainException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\ReferralCommission;
use App\Models\ReferralVisit;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Settings\AffiliateSettings;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AffiliateService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly AffiliateSettings $settings,
    ) {}

    public function recordVisit(User $referrer, ?string $ip, ?string $userAgent, ?string $landingUrl): void
    {
        ReferralVisit::create([
            'referrer_id' => $referrer->getKey(),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'landing_url' => $landingUrl,
        ]);
    }

    /** Bind a new account to whoever referred it. Self-referral is silently ignored. */
    public function attachReferrer(User $user, ?string $code): void
    {
        if (! $this->settings->enabled || ! $code) {
            return;
        }

        $referrer = User::where('referral_code', mb_strtoupper(trim($code)))->first();

        if ($referrer === null || $referrer->is($user)) {
            return;
        }

        $user->forceFill(['referred_by_id' => $referrer->getKey()])->save();
    }

    /**
     * Pay the referrer for a completed order.
     *
     * The rate is snapshotted onto the commission row, so changing the programme later
     * never rewrites what someone was already promised.
     */
    public function onOrderCompleted(Order $order): ?ReferralCommission
    {
        if (! $this->settings->enabled || $order->user_id === null) {
            return null;
        }

        $buyer = $order->user;
        $referrer = $buyer?->referrer;

        if ($buyer === null || $referrer === null) {
            return null;
        }

        if (ReferralCommission::where('order_id', $order->getKey())->exists()) {
            return null;
        }

        if ($this->settings->trigger === 'first_order'
            && ReferralCommission::where('referred_user_id', $buyer->getKey())->exists()) {
            return null;
        }

        $base = $order->total - $order->discount;
        $amount = $this->settings->commission_type === 'percent'
            ? Money::percent($base, $this->settings->commission_value)
            : $this->settings->commission_value;

        if ($amount <= 0) {
            return null;
        }

        $autoApprove = $this->settings->approval === 'auto_on_complete';

        return DB::transaction(function () use ($order, $buyer, $referrer, $base, $amount, $autoApprove): ReferralCommission {
            $commission = ReferralCommission::create([
                'referrer_id' => $referrer->getKey(),
                'referred_user_id' => $buyer->getKey(),
                'order_id' => $order->getKey(),
                'base_amount' => $base,
                'rate_snapshot' => $this->settings->commission_type === 'percent'
                    ? $this->settings->commission_value.'%'
                    : Money::format($this->settings->commission_value),
                'amount' => $amount,
                'status' => CommissionStatus::Pending,
            ]);

            if ($autoApprove) {
                $this->approve($commission);
            }

            return $commission->refresh();
        });
    }

    /** Approving credits the referrer's wallet; the ledger entry is the payout record. */
    public function approve(ReferralCommission $commission, ?Admin $admin = null): ReferralCommission
    {
        if ($commission->status === CommissionStatus::Approved) {
            return $commission;
        }

        DB::transaction(function () use ($commission, $admin): void {
            $commission->forceFill([
                'status' => CommissionStatus::Approved,
                'approved_at' => now(),
            ])->save();

            $this->wallet->credit(
                $commission->referrer,
                $commission->amount,
                WalletTxType::ReferralBonus,
                $commission,
                "commission:{$commission->getKey()}",
                'Referral commission',
                $admin,
            );
        });

        return $commission->refresh();
    }

    /**
     * Take back a commission whose order was refunded or cancelled.
     *
     * If it was already paid into the wallet, the money comes back out; a balance that is
     * short simply goes negative-proof by debiting what is there, because the customer
     * should never be able to withdraw a commission for a reversed sale.
     */
    public function reverseForOrder(Order $order, string $reason): void
    {
        $commission = ReferralCommission::where('order_id', $order->getKey())->first();

        if ($commission === null || $commission->status === CommissionStatus::Reversed) {
            return;
        }

        $this->reverse($commission, $reason);
    }

    public function reverse(ReferralCommission $commission, string $reason, ?Admin $admin = null): ReferralCommission
    {
        DB::transaction(function () use ($commission, $reason, $admin): void {
            $wasApproved = $commission->status === CommissionStatus::Approved;

            $commission->forceFill([
                'status' => CommissionStatus::Reversed,
                'reversed_reason' => $reason,
            ])->save();

            if (! $wasApproved) {
                return;
            }

            $referrer = $commission->referrer;
            $recoverable = min($commission->amount, (int) $referrer->balance);

            if ($recoverable > 0) {
                $this->wallet->debit(
                    $referrer,
                    $recoverable,
                    WalletTxType::AdminDebit,
                    $commission,
                    "commission-reversal:{$commission->getKey()}",
                    "Referral commission reversed: {$reason}",
                    $admin,
                );
            }
        });

        return $commission->refresh();
    }

    /**
     * Ask to cash out. The money leaves the wallet immediately and is held against the
     * request, so it cannot also be spent on an order while the request is queued.
     *
     * @param  array<string, string>  $accountDetails
     */
    public function requestWithdrawal(User $user, int $amount, string $method, array $accountDetails): WithdrawalRequest
    {
        if (! $this->settings->enabled) {
            throw new DomainException('The affiliate programme is currently closed.');
        }

        if ($amount < $this->settings->min_withdrawal) {
            throw new DomainException('The minimum withdrawal is '.Money::format($this->settings->min_withdrawal).'.');
        }

        return DB::transaction(function () use ($user, $amount, $method, $accountDetails): WithdrawalRequest {
            $request = WithdrawalRequest::create([
                'request_number' => 'WD-'.now()->format('ym').'-'.Str::upper(Str::random(6)),
                'user_id' => $user->getKey(),
                'amount' => $amount,
                'method' => $method,
                'account_details' => $accountDetails,
                'status' => WithdrawalStatus::Pending,
            ]);

            $this->wallet->debit(
                $user,
                $amount,
                WalletTxType::Withdrawal,
                $request,
                "withdrawal:{$request->getKey()}",
                "Withdrawal request {$request->request_number}",
            );

            WithdrawalRequested::dispatch($request);

            return $request;
        });
    }

    public function approveWithdrawal(WithdrawalRequest $request, Admin $admin, ?string $note = null): WithdrawalRequest
    {
        $this->guardPending($request);

        $request->forceFill([
            'status' => WithdrawalStatus::Approved,
            'admin_note' => $note,
            'processed_by' => $admin->getKey(),
        ])->save();

        return $request;
    }

    public function markWithdrawalPaid(WithdrawalRequest $request, Admin $admin, ?string $note = null): WithdrawalRequest
    {
        if ($request->status === WithdrawalStatus::Rejected) {
            throw new DomainException('A rejected withdrawal cannot be marked paid.');
        }

        $request->forceFill([
            'status' => WithdrawalStatus::Paid,
            'admin_note' => $note ?: $request->admin_note,
            'processed_by' => $admin->getKey(),
            'processed_at' => now(),
        ])->save();

        WithdrawalProcessed::dispatch($request);

        return $request;
    }

    /** Rejection returns the held funds to the wallet. */
    public function rejectWithdrawal(WithdrawalRequest $request, Admin $admin, string $reason): WithdrawalRequest
    {
        $this->guardPending($request);

        DB::transaction(function () use ($request, $admin, $reason): void {
            $request->forceFill([
                'status' => WithdrawalStatus::Rejected,
                'admin_note' => $reason,
                'processed_by' => $admin->getKey(),
                'processed_at' => now(),
            ])->save();

            $this->wallet->credit(
                $request->user,
                $request->amount,
                WalletTxType::WithdrawalReversal,
                $request,
                "withdrawal-reversal:{$request->getKey()}",
                "Withdrawal {$request->request_number} rejected: {$reason}",
                $admin,
            );
        });

        WithdrawalProcessed::dispatch($request->refresh());

        return $request;
    }

    private function guardPending(WithdrawalRequest $request): void
    {
        if (! in_array($request->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true)) {
            throw new DomainException('This withdrawal has already been processed.');
        }
    }

    /** @return array<string, int> */
    public function statsFor(User $user): array
    {
        return [
            'clicks' => ReferralVisit::where('referrer_id', $user->getKey())->count(),
            'signups' => User::where('referred_by_id', $user->getKey())->count(),
            'pending' => (int) ReferralCommission::where('referrer_id', $user->getKey())
                ->where('status', CommissionStatus::Pending)->sum('amount'),
            'approved' => (int) ReferralCommission::where('referrer_id', $user->getKey())
                ->where('status', CommissionStatus::Approved)->sum('amount'),
            'withdrawn' => (int) WithdrawalRequest::where('user_id', $user->getKey())
                ->where('status', WithdrawalStatus::Paid)->sum('amount'),
        ];
    }
}
