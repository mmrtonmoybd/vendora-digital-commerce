<?php

declare(strict_types=1);

use App\Enums\DepositStatus;
use App\Models\Admin;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\DepositService;

it('lets an admin credit a customer wallet by recording a deposit', function (): void {
    $admin = Admin::factory()->create();
    $customer = User::factory()->create();
    $method = PaymentMethod::factory()->create();

    $deposit = Deposit::create([
        'deposit_number' => 'DP-MANUAL-1',
        'user_id' => $customer->id,
        'payment_method_id' => $method->id,
        'amount' => 75_000,
        'fee' => 0,
        'payable' => 75_000,
        'status' => DepositStatus::Pending,
    ]);

    app(DepositService::class)->forceApprove($deposit, $admin, 'Cash received in the office.');

    expect($customer->fresh()->balance)->toBe(75_000)
        ->and($deposit->fresh()->status)->toBe(DepositStatus::Completed)
        ->and($deposit->fresh()->admin_note)->toContain('Cash received in the office.')
        // It shows up in the customer's own deposit history, not just the ledger.
        ->and($customer->deposits()->count())->toBe(1);
});

it('credits the wallet only once however many times approval is retried', function (): void {
    $admin = Admin::factory()->create();
    $customer = User::factory()->create();
    $method = PaymentMethod::factory()->create();

    $deposit = Deposit::create([
        'deposit_number' => 'DP-MANUAL-2',
        'user_id' => $customer->id,
        'payment_method_id' => $method->id,
        'amount' => 20_000, 'fee' => 0, 'payable' => 20_000,
        'status' => DepositStatus::Pending,
    ]);

    app(DepositService::class)->forceApprove($deposit, $admin, 'First');

    expect(fn () => app(DepositService::class)->forceApprove($deposit->fresh(), $admin, 'Again'))
        ->toThrow(App\Exceptions\DomainException::class, 'already completed');

    expect($customer->fresh()->balance)->toBe(20_000)
        ->and(App\Models\WalletTransaction::where('user_id', $customer->id)->count())->toBe(1);
});

it('refuses a manual approval without a reason', function (): void {
    $admin = Admin::factory()->create();
    $customer = User::factory()->create();
    $method = PaymentMethod::factory()->create();

    $deposit = Deposit::create([
        'deposit_number' => 'DP-MANUAL-3',
        'user_id' => $customer->id, 'payment_method_id' => $method->id,
        'amount' => 1_000, 'fee' => 0, 'payable' => 1_000,
        'status' => DepositStatus::Pending,
    ]);

    expect(fn () => app(DepositService::class)->forceApprove($deposit, $admin, '  '))
        ->toThrow(App\Exceptions\DomainException::class, 'A reason is required');

    expect($customer->fresh()->balance)->toBe(0);
});
