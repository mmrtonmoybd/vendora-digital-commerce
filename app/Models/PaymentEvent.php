<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per callback received, whatever it claimed. The audit trail for disputes. */
final class PaymentEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /** @return BelongsTo<Payment, self> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
