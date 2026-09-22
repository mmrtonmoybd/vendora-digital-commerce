<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WithdrawalStatus;
use App\Support\Money;
use Database\Factories\WithdrawalRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WithdrawalRequest extends Model
{
    /** @use HasFactory<WithdrawalRequestFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount' => 'integer',
            'account_details' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'request_number';
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, self> */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }

    /** Funds are held from the moment the request is made; only a rejection returns them. */
    public function isHolding(): bool
    {
        return in_array($this->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true);
    }
}
