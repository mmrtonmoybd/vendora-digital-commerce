<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccessStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_snapshot' => ['name' => fake()->words(3, true), 'price' => 1_000_00],
            'unit_price' => 1_000_00,
            'quantity' => 1,
            'line_total' => 1_000_00,
            'delivery_email' => fake()->safeEmail(),
            'access_status' => AccessStatus::Awaiting,
        ];
    }

    public function granted(): static
    {
        return $this->state(fn (): array => [
            'access_status' => AccessStatus::Granted,
            'granted_at' => now(),
        ]);
    }
}
