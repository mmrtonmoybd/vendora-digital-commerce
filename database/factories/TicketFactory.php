<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
final class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ticket_number' => 'TK-'.Str::upper(Str::random(8)),
            'user_id' => User::factory(),
            'subject' => fake()->sentence(5),
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::Open,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => TicketStatus::Closed, 'closed_at' => now()]);
    }
}
