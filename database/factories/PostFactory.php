<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
final class PostFactory extends Factory
{
    protected $model = Post::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => $title = fake()->sentence(5),
            'slug' => Str::slug($title).'-'.Str::random(5),
            'excerpt' => fake()->sentence(12),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'gradient' => 'from-brand-500 to-brand-700',
            'published_at' => now()->subDay(),
            'is_published' => true,
            'read_minutes' => 3,
            'views' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['is_published' => false, 'published_at' => null]);
    }
}
