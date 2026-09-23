<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DepositStatus;
use App\Support\Money;
use Database\Factories\DepositFactory;
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
 * @property string $deposit_number
 * @property int $user_id
 * @property int $payment_method_id
 * @property int $amount
 * @property int $fee
 * @property int $payable
 * @property ?string $sender_number
 * @property ?string $transaction_id
 * @property ?string $screenshot
 * @property DepositStatus $status
 * @property ?string $admin_note
 * @property ?string $reject_reason
 * @property ?int $processed_by
 * @property ?Carbon $processed_at
 * @property ?int $payment_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
            'amount' => 'integer',
            'fee' => 'integer',
            'payable' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }

    /** Another deposit or order already claimed this TrxID on the same method. */
    public function hasDuplicateTransactionId(): bool
    {
        if (! $this->transaction_id) {
            return false;
        }

        $inDeposits = self::query()
            ->where('payment_method_id', $this->payment_method_id)
            ->where('transaction_id', $this->transaction_id)
            ->whereKeyNot($this->getKey())
            ->exists();

        $inOrders = Order::query()
            ->where('payment_method_id', $this->payment_method_id)
            ->where('manual_transaction_id', $this->transaction_id)
            ->exists();

        return $inDeposits || $inOrders;
    }
}
