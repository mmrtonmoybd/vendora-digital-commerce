<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
final class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'label' => fake()->words(2, true),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_order' => 0,
            'max_discount' => null,
            'usage_limit' => null,
            'per_user_limit' => null,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    /** A flat discount in paisa rather than a percentage. */
    public function fixed(int $paisa): static
    {
        return $this->state(fn (): array => ['type' => CouponType::Fixed, 'value' => $paisa]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
