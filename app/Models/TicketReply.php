<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property int $ticket_id
 * @property string $author_type
 * @property int $author_id
 * @property string $message
 * @property bool $is_internal_note
 * @property ?array $attachments
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
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

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return MorphTo<Model, $this> */
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
        $author = $this->author;

        // A morphTo resolves to Model as far as static analysis is concerned; the two
        // things that actually write replies are a customer and a staff member.
        return $author instanceof User || $author instanceof Admin
            ? $author->name
            : 'Unknown';
    }
}
