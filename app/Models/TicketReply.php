<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class TicketReply extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_internal_note' => 'boolean',
        ];
    }

    /** @return BelongsTo<Ticket, self> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return MorphTo<Model, self> */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function isFromStaff(): bool
    {
        return $this->author_type === Admin::class;
    }

    public function authorName(): string
    {
        return $this->author?->name ?? 'Unknown';
    }
}
