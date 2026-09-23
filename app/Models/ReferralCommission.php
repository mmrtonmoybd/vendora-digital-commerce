<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionStatus;
use App\Support\Money;
use Database\Factories\ReferralCommissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $referrer_id
 * @property int $referred_user_id
 * @property int $order_id
 * @property int $base_amount
 * @property string $rate_snapshot
 * @property int $amount
 * @property CommissionStatus $status
 * @property ?Carbon $approved_at
 * @property ?string $reversed_reason
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class ReferralCommission extends Model
{
    /** @use HasFactory<ReferralCommissionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => CommissionStatus::class,
            'base_amount' => 'integer',
            'amount' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }
}
