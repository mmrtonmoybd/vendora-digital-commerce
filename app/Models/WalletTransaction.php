<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletTxType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

/**
 * The wallet ledger. Append-only by design: a balance you cannot reconstruct from its
 * history is a balance nobody can defend in a dispute.
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
        static::updating(function (): never {
            throw new RuntimeException('Wallet transactions are immutable. Post a compensating entry instead.');
        });

        static::deleting(function (): never {
            throw new RuntimeException('Wallet transactions cannot be deleted.');
        });
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, self> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** @return MorphTo<Model, self> */
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
