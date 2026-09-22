<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** The timeline of one purchased unit's access. Written by AccessFulfillmentService only. */
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

    /** @return BelongsTo<OrderItem, self> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return MorphTo<Model, self> */
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
