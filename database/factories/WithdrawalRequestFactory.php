<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WithdrawalRequest>
 */
final class WithdrawalRequestFactory extends Factory
{
    protected $model = WithdrawalRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'request_number' => 'WD-'.Str::upper(Str::random(8)),
            'user_id' => User::factory(),
            'amount' => 1_000_00,
            'method' => 'bkash',
            'account_details' => ['number' => '01700000000'],
            'status' => WithdrawalStatus::Pending,
        ];
    }
}
