<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    /** @return BelongsTo<Order, self> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
