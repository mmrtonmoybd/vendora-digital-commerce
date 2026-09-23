<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletTxType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * The wallet ledger. Append-only by design: a balance you cannot reconstruct from its
 * history is a balance nobody can defend in a dispute.
 */
/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $user_id
 * @property WalletTxType $type
 * @property int $amount
 * @property int $balance_before
 * @property int $balance_after
 * @property string $idempotency_key
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property ?string $description
 * @property ?int $admin_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class WalletTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => WalletTxType::class,
            'amount' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::updating(function (): never {
            throw new RuntimeException('Wallet transactions are immutable. Post a compensating entry instead.');
        });

        self::deleting(function (): never {
            throw new RuntimeException('Wallet transactions cannot be deleted.');
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function formattedAmount(): string
    {
        return ($this->isCredit() ? '+' : '').Money::format($this->amount);
    }
}
