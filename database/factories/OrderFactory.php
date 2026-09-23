<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_number' => 'VD-'.now()->format('ym').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'delivery_email' => fake()->safeEmail(),
            'subtotal' => 1_000_00,
            'discount' => 0,
            'fee' => 0,
            'total' => 1_000_00,
            'refunded_amount' => 0,
            'payment_method_id' => PaymentMethod::factory(),
            'payment_status' => OrderPaymentStatus::Unpaid,
            'status' => OrderStatus::PendingPayment,
            'sla_alerted' => false,
        ];
    }

    /**
     * Paid, as OrderService leaves it.
     *
     * A factory state rather than a call to the service, so a test that needs a paid order
     * as a fixture does not also exercise the state machine it is not testing.
     */
    public function paid(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => OrderPaymentStatus::Paid,
            'status' => OrderStatus::Pending,
            'paid_at' => now(),
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'guest_email' => fake()->safeEmail(),
        ]);
    }
}
