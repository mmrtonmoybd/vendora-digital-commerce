<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethodType;
use App\Models\Concerns\HasContentTranslations;
use App\Support\Money;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property PaymentMethodType $type
 * @property ?string $driver
 * @property string $chip_color
 * @property string $short
 * @property ?string $logo
 * @property ?string $account_number
 * @property ?string $account_type
 * @property ?string $instructions
 * @property int $min_amount
 * @property ?int $max_amount
 * @property int $fee_bps
 * @property int $fee_fixed
 * @property bool $available_for_orders
 * @property bool $available_for_deposits
 * @property bool $is_active
 * @property int $sort_order
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class PaymentMethod extends Model
{
    use HasContentTranslations;

    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Text an administrator typed that a second language needs its own version of.
     *
     * Read through t(), never through the attribute directly — see the trait.
     *
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return [
            'name',
            'short',
            'instructions',
        ];
    }

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
