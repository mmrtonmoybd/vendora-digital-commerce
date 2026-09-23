<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $payable_type
 * @property int $payable_id
 * @property ?int $user_id
 * @property string $gateway
 * @property string $merchant_order_id
 * @property ?string $sp_order_id
 * @property int $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property ?string $sp_code
 * @property ?string $sp_message
 * @property ?string $method
 * @property ?string $bank_trx_id
 * @property ?string $invoice_no
 * @property ?string $checkout_url
 * @property ?array $request_payload
 * @property ?array $verify_response
 * @property ?Carbon $verified_at
 * @property ?Carbon $last_checked_at
 * @property int $attempts
 * @property ?string $ip
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
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

    /** @return MorphTo<Model, $this> */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PaymentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->latest();
    }

    /**
     * Statuses that mean the gateway has not finished with this payment.
     *
     * A named list rather than an inline array: the filter in the admin table needs the
     * same definition, and it is handed an untyped query builder that cannot reach the
     * scope.
     *
     * @return array<int, string>
     */
    public static function openStatuses(): array
    {
        return [PaymentStatus::Initiated->value, PaymentStatus::Pending->value];
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', self::openStatuses());
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
