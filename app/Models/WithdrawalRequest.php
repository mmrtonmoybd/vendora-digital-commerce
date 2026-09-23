<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WithdrawalStatus;
use App\Support\Money;
use Database\Factories\WithdrawalRequestFactory;
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
 * @property string $request_number
 * @property int $user_id
 * @property int $amount
 * @property string $method
 * @property array $account_details
 * @property WithdrawalStatus $status
 * @property ?string $admin_note
 * @property ?int $processed_by
 * @property ?Carbon $processed_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> */
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
