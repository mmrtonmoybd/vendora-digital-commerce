<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FaqGroup;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
final class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'question' => rtrim(fake()->sentence(6), '.').'?',
            'answer' => fake()->paragraph(),
            'group' => FaqGroup::General,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
