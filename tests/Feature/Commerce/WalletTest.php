<?php

declare(strict_types=1);

use App\Enums\WalletTxType;
use App\Exceptions\InsufficientBalance;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;

beforeEach(function (): void {
    $this->wallet = app(WalletService::class);
});

it('credits the balance and writes a ledger row', function (): void {
    $user = User::factory()->create();

    $tx = $this->wallet->credit($user, 50_000, WalletTxType::Deposit, null, 'k1', 'Top-up');

    expect($user->fresh()->balance)->toBe(50_000)
        ->and($tx->balance_before)->toBe(0)
        ->and($tx->balance_after)->toBe(50_000)
        ->and($tx->amount)->toBe(50_000);
});

it('debits the balance and records the running total', function (): void {
    $user = User::factory()->withBalance(100_000)->create();

    $tx = $this->wallet->debit($user, 30_000, WalletTxType::OrderPayment, null, 'k2');

    expect($user->fresh()->balance)->toBe(70_000)
        ->and($tx->amount)->toBe(-30_000)
        ->and($tx->balance_after)->toBe(70_000);
});

it('refuses to overdraw a wallet', function (): void {
    $user = User::factory()->withBalance(1_000)->create();

    expect(fn () => $this->wallet->debit($user, 5_000, WalletTxType::OrderPayment, null, 'k3'))
        ->toThrow(InsufficientBalance::class);

    // The failed attempt must leave no trace at all.
    expect($user->fresh()->balance)->toBe(1_000)
        ->and(WalletTransaction::count())->toBe(0);
});

it('applies a repeated idempotency key exactly once', function (): void {
    $user = User::factory()->create();

    $first = $this->wallet->credit($user, 25_000, WalletTxType::Deposit, null, 'same-key');
    $second = $this->wallet->credit($user, 25_000, WalletTxType::Deposit, null, 'same-key');

    expect($second->id)->toBe($first->id)
        ->and($user->fresh()->balance)->toBe(25_000)
        ->and(WalletTransaction::count())->toBe(1);
});

it('refuses to let a ledger row be edited or deleted', function (): void {
    $user = User::factory()->create();
    $tx = $this->wallet->credit($user, 10_000, WalletTxType::Deposit, null, 'k4');

    expect(fn () => $tx->update(['amount' => 999]))->toThrow(RuntimeException::class);
    expect(fn () => $tx->delete())->toThrow(RuntimeException::class);
});

it('reports no drift when every movement went through the service', function (): void {
    $user = User::factory()->create();

    $this->wallet->credit($user, 80_000, WalletTxType::Deposit, null, 'a');
    $this->wallet->debit($user, 30_000, WalletTxType::OrderPayment, null, 'b');
    $this->wallet->credit($user, 5_000, WalletTxType::ReferralBonus, null, 'c');

    expect($user->fresh()->balance)->toBe(55_000)
        ->and($this->wallet->drift($user->fresh()))->toBe(0);
});

it('detects and repairs drift caused by a write outside the service', function (): void {
    $user = User::factory()->create();
    $this->wallet->credit($user, 40_000, WalletTxType::Deposit, null, 'd');

    // Somebody wrote the column directly. This is the bug reconcile exists to catch.
    $user->forceFill(['balance' => 99_999])->save();

    expect($this->wallet->drift($user->fresh()))->toBe(59_999);

    $drift = $this->wallet->reconcile($user->fresh());

    expect($drift)->toBe(59_999)
        ->and($user->fresh()->balance)->toBe(40_000);
});
