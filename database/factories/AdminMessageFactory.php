<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageAudience;
use App\Models\AdminMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminMessage>
 */
final class AdminMessageFactory extends Factory
{
    protected $model = AdminMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'audience' => MessageAudience::All,
            'audience_meta' => null,
            'send_email' => false,
            'recipient_count' => 0,
        ];
    }
}
