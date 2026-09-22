<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => OrderPaymentStatus::class,
            'subtotal' => 'integer',
            'discount' => 'integer',
            'fee' => 'integer',
            'total' => 'integer',
            'refunded_amount' => 'integer',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'assigned_at' => 'datetime',
            'sla_alerted' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStatusHistory> */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->oldest();
    }

    /** @return BelongsTo<Coupon, self> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<PaymentMethod, self> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** @return BelongsTo<Admin, self> */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    /** @return BelongsTo<Admin, self> */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /** @return MorphMany<Payment> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest();
    }

    /** @return HasMany<Ticket> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return BelongsTo<ReferralCommission, self> */
    public function commission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReferralCommission::class);
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [
            OrderStatus::PendingPayment->value,
            OrderStatus::Pending->value,
            OrderStatus::Processing->value,
        ]);
    }

    /** @param Builder<self> $query */
    public function scopePaid(Builder $query): void
    {
        $query->whereIn('payment_status', [
            OrderPaymentStatus::Paid->value,
            OrderPaymentStatus::PartiallyRefunded->value,
        ]);
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, [
            OrderPaymentStatus::Paid,
            OrderPaymentStatus::PartiallyRefunded,
        ], true);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    public function customerEmail(): string
    {
        return $this->user?->email ?? (string) $this->guest_email;
    }

    public function refundableAmount(): int
    {
        return max(0, $this->total - $this->refunded_amount);
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total);
    }

    /** Short human summary of the line items, for tables and emails. */
    public function itemsSummary(): string
    {
        $names = $this->items->map(fn (OrderItem $item): string => ($item->product_snapshot['name'] ?? 'Item').' × '.$item->quantity);

        return $names->implode(', ');
    }

    public function needsManualVerification(): bool
    {
        return $this->payment_status === OrderPaymentStatus::Submitted;
    }

    /** Items still waiting for an admin to grant access on the platform. */
    public function hasOpenAccessItems(): bool
    {
        return $this->items->contains(fn (OrderItem $item): bool => $item->access_status->isOpen());
    }

    /** @param \Illuminate\Database\Eloquent\Builder<self> $query */
    public function scopeAwaitingAccess(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->paid()->whereHas('items', fn ($q) => $q->whereIn('access_status', [
            \App\Enums\AccessStatus::Awaiting->value,
            \App\Enums\AccessStatus::InProgress->value,
        ]));
    }
}
