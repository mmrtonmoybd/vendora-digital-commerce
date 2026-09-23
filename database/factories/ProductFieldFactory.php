<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\Product;
use App\Models\ProductField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductField>
 */
final class ProductFieldFactory extends Factory
{
    protected $model = ProductField::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'key' => Str::slug(fake()->word()),
            'label' => fake()->words(2, true),
            'type' => FieldType::Text,
            'options' => null,
            'placeholder' => null,
            'required' => false,
            'sort_order' => 0,
        ];
    }

    /** @param array<int, string> $options */
    public function select(array $options): static
    {
        return $this->state(fn (): array => ['type' => FieldType::Select, 'options' => $options]);
    }
}
