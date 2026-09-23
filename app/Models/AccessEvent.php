<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/** The timeline of one purchased unit's access. Written by AccessFulfillmentService only. */
/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $order_item_id
 * @property AccessEventType $type
 * @property ?string $note
 * @property ?string $actor_type
 * @property ?int $actor_id
 * @property ?array $meta
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class AccessEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => AccessEventType::class,
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
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
