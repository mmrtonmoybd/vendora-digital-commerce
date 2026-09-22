<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethodType;
use App\Support\Money;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'min_amount' => 'integer',
            'max_amount' => 'integer',
            'fee_bps' => 'integer',
            'fee_fixed' => 'integer',
            'available_for_orders' => 'boolean',
            'available_for_deposits' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeForOrders(Builder $query): void
    {
        $query->active()->where('available_for_orders', true)->orderBy('sort_order');
    }

    /** @param Builder<self> $query */
    public function scopeForDeposits(Builder $query): void
    {
        $query->active()->where('available_for_deposits', true)->orderBy('sort_order');
    }

    public function isWallet(): bool
    {
        return $this->type === PaymentMethodType::Wallet;
    }

    public function isManual(): bool
    {
        return $this->type === PaymentMethodType::Manual;
    }

    public function isGateway(): bool
    {
        return $this->type === PaymentMethodType::Gateway;
    }

    /** Fee on an amount in paisa: a basis-point share plus a flat component. */
    public function feeFor(int $amount): int
    {
        return Money::percentOfBps($amount, $this->fee_bps) + $this->fee_fixed;
    }

    public function acceptsAmount(int $amount): bool
    {
        if ($amount < $this->min_amount) {
            return false;
        }

        return $this->max_amount === null || $amount <= $this->max_amount;
    }
}
