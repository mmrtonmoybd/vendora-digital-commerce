<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\CustomInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomInvoice>
 */
final class CustomInvoiceFactory extends Factory
{
    protected $model = CustomInvoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'user_id' => User::factory(),
            'product_name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'amount' => 150_00,
            'status' => InvoiceStatus::Unpaid,
            'due_at' => now()->addWeek(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
