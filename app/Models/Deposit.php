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

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** @return BelongsTo<Payment, self> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return MorphMany<Payment> */
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
