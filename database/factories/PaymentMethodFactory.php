<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PaymentMethod> */
final class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => 'bKash',
            'key' => 'bkash-'.Str::lower(Str::random(5)),
            'type' => PaymentMethodType::Manual,
            'chip_color' => 'bg-pink-600',
            'short' => 'bK',
            'account_number' => '01700000000',
            'account_type' => 'Personal',
            'instructions' => 'Send money and paste the transaction ID.',
            'min_amount' => 0,
            'fee_bps' => 0,
            'fee_fixed' => 0,
            'available_for_orders' => true,
            'available_for_deposits' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function wallet(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Wallet balance',
            'key' => 'wallet',
            'type' => PaymentMethodType::Wallet,
            'chip_color' => 'bg-stone-900',
            'short' => 'W',
            'account_number' => null,
            'account_type' => null,
            'available_for_deposits' => false,
        ]);
    }

    public function gateway(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Card / Online',
            'key' => 'card',
            'type' => PaymentMethodType::Gateway,
            'driver' => 'shurjopay',
            'chip_color' => 'bg-brand-700',
            'short' => 'C',
            'account_number' => null,
            'account_type' => null,
        ]);
    }

    public function withFee(int $bps, int $fixed = 0): static
    {
        return $this->state(fn (): array => ['fee_bps' => $bps, 'fee_fixed' => $fixed]);
    }
}
