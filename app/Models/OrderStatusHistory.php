<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $order_id
 * @property ?OrderStatus $from_status
 * @property OrderStatus $to_status
 * @property ?string $note
 * @property ?string $actor_type
 * @property ?int $actor_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class OrderStatusHistory extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return MorphTo<Model, $this> */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function actorName(): string
    {
        return match (true) {
            $this->actor instanceof Admin => $this->actor->name.' (staff)',
            $this->actor instanceof User => $this->actor->name,
            default => 'System',
        };
    }
}
