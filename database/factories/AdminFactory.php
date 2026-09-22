<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Admin> */
final class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }
}
