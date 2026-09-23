<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $order_number
 * @property ?int $user_id
 * @property ?string $guest_email
 * @property string $delivery_email
 * @property int $subtotal
 * @property int $discount
 * @property int $fee
 * @property int $total
 * @property int $refunded_amount
 * @property ?int $coupon_id
 * @property ?int $payment_method_id
 * @property OrderPaymentStatus $payment_status
 * @property OrderStatus $status
 * @property ?string $manual_sender_number
 * @property ?string $manual_transaction_id
 * @property ?string $manual_screenshot
 * @property ?string $customer_note
 * @property ?string $delivery_note
 * @property ?string $refund_reason
 * @property ?string $customer_phone
 * @property ?Carbon $paid_at
 * @property ?Carbon $completed_at
 * @property ?Carbon $cancelled_at
 * @property ?int $processed_by
 * @property ?int $assigned_admin_id
 * @property ?Carbon $assigned_at
 * @property bool $sla_alerted
 * @property ?string $ip
 * @property ?string $user_agent
 * @property ?int $order_source_id
 * @property ?string $utm_source
 * @property ?string $utm_medium
 * @property ?string $utm_campaign
 * @property ?string $utm_term
 * @property ?string $utm_content
 * @property ?string $fbclid
 * @property ?string $ttclid
 * @property ?string $gclid
 * @property ?string $fbp
 * @property ?string $fbc
 * @property ?string $referrer
 * @property ?string $landing_url
 * @property ?int $first_touch_source_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The order's timeline, oldest first.
     *
     * Ordered by id rather than created_at: several transitions can land in the same
     * second, and a timeline that reorders itself is worse than no timeline.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    /** @return BelongsTo<Admin, $this> */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /**
     * Which platform sent this customer — the last touch before they bought.
     *
     * @return BelongsTo<OrderSource, $this>
     */
    public function orderSource(): BelongsTo
    {
        return $this->belongsTo(OrderSource::class);
    }

    /**
     * Where they first arrived from, kept separately so both questions stay answerable.
     *
     * @return BelongsTo<OrderSource, $this>
     */
    public function firstTouchSource(): BelongsTo
    {
        return $this->belongsTo(OrderSource::class, 'first_touch_source_id');
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest();
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasOne<ReferralCommission, $this> */
    public function commission(): HasOne
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

    /** @param Builder<self> $query */
    public function scopeAwaitingAccess(Builder $query): void
    {
        $query->paid()->whereHas('items', fn ($q) => $q->whereIn('access_status', [
            AccessStatus::Awaiting->value,
            AccessStatus::InProgress->value,
        ]));
    }
}
