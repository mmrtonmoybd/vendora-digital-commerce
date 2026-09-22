<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReferralCommission extends Model
{
    /** @use HasFactory<\Database\Factories\ReferralCommissionFactory> */
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

    /** @return BelongsTo<User, self> */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /** @return BelongsTo<User, self> */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /** @return BelongsTo<Order, self> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }
}
