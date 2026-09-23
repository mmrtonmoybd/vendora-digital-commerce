<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommissionStatus;
use App\Models\Order;
use App\Models\ReferralCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralCommission>
 */
final class ReferralCommissionFactory extends Factory
{
    protected $model = ReferralCommission::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'referrer_id' => User::factory(),
            'referred_user_id' => User::factory(),
            'order_id' => Order::factory(),
            'base_amount' => 1_000_00,
            'rate_snapshot' => '500',
            'amount' => 50_00,
            'status' => CommissionStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => CommissionStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
