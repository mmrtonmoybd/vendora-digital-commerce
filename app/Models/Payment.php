<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'request_payload' => 'array',
            'verify_response' => 'array',
            'verified_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /** @return MorphTo<Model, self> */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PaymentEvent> */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->latest();
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [PaymentStatus::Initiated->value, PaymentStatus::Pending->value]);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }

    /** What the payable is, as stored in value2 on the gateway request. */
    public function payableKind(): string
    {
        return match ($this->payable_type) {
            Order::class => 'order',
            Deposit::class => 'deposit',
            CustomInvoice::class => 'invoice',
            default => 'unknown',
        };
    }
}
