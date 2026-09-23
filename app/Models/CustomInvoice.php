<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Support\Money;
use Database\Factories\CustomInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $invoice_number
 * @property int $user_id
 * @property string $product_name
 * @property ?string $description
 * @property int $amount
 * @property ?string $note
 * @property InvoiceStatus $status
 * @property ?Carbon $due_at
 * @property ?Carbon $paid_at
 * @property ?string $paid_via
 * @property ?int $payment_id
 * @property ?int $created_by
 * @property ?int $order_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class CustomInvoice extends Model
{
    /** @use HasFactory<CustomInvoiceFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'amount' => 'integer',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'invoice_number';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest();
    }

    public function isPayable(): bool
    {
        return $this->status === InvoiceStatus::Unpaid;
    }

    public function isOverdue(): bool
    {
        return $this->isPayable() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }
}
