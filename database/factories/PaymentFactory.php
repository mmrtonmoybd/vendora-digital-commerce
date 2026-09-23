<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payable_type' => Order::class,
            'payable_id' => Order::factory(),
            'user_id' => User::factory(),
            'gateway' => 'shurjopay',
            'merchant_order_id' => Str::upper(Str::random(12)),
            'amount' => 1_000_00,
            'currency' => 'BDT',
            'status' => PaymentStatus::Initiated,
            'attempts' => 0,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Paid,
            'sp_code' => '1000',
            'verified_at' => now(),
        ]);
    }
}
