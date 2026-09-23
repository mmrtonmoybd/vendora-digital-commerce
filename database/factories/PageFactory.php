<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
final class PageFactory extends Factory
{
    protected $model = Page::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => $title = fake()->sentence(3),
            'slug' => Str::slug($title).'-'.Str::random(5),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'template' => 'default',
            'is_active' => true,
            'show_in_footer' => false,
        ];
    }
}
