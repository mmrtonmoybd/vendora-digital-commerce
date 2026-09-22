<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '01'.fake()->numerify('#########'),
            'email_verified_at' => now(),
            'password' => 'password',
            'balance' => 0,
            'referral_code' => Str::upper(Str::random(8)),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Suspended]);
    }

    /**
     * Give the customer a starting balance.
     *
     * The column is set directly here because a factory is not a transaction; tests that
     * care about the ledger use WalletService and assert on the rows it writes.
     */
    public function withBalance(int $paisa): static
    {
        return $this->state(fn (): array => ['balance' => $paisa]);
    }
}
