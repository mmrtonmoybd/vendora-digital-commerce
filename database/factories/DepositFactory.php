<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deposit>
 */
final class DepositFactory extends Factory
{
    protected $model = Deposit::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'deposit_number' => 'DEP-'.Str::upper(Str::random(8)),
            'user_id' => User::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'amount' => 500_00,
            'fee' => 0,
            'payable' => 500_00,
            'status' => DepositStatus::Pending,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'sender_number' => '01700000000',
            'transaction_id' => Str::upper(Str::random(10)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => DepositStatus::Completed,
            'processed_at' => now(),
        ]);
    }
}
