<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        // Prices are paisa, and always a whole number of taka here so assertions read
        // the way a person would say them.
        $price = fake()->numberBetween(100, 5000) * 100;

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'short' => fake()->sentence(6),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'icon_key' => 'play',
            'gradient' => 'from-rose-500 to-red-700',
            'price' => $price,
            'regular_price' => (int) round($price * 1.3),
            'stock' => fake()->numberBetween(5, 100),
            'min_qty' => 1,
            'max_qty' => 10,
            'is_active' => true,
            'requires_delivery_email' => true,
            'sort_order' => 0,
            'fulfillment_sla_minutes' => 30,
            'warranty_days' => 7,
            'rating' => 49,
            'reviews_count' => fake()->numberBetween(0, 500),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock' => 0]);
    }

    public function withStock(int $stock): static
    {
        return $this->state(fn (): array => ['stock' => $stock]);
    }

    public function priced(int $paisa): static
    {
        return $this->state(fn (): array => ['price' => $paisa, 'regular_price' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
