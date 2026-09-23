<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $ticket_number
 * @property int $user_id
 * @property ?int $order_id
 * @property string $subject
 * @property TicketPriority $priority
 * @property TicketStatus $status
 * @property ?int $assigned_admin_id
 * @property ?Carbon $first_responded_at
 * @property ?Carbon $last_reply_at
 * @property ?Carbon $closed_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'first_responded_at' => 'datetime',
            'last_reply_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'ticket_number';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /** @return HasMany<TicketReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }

    /**
     * What the customer is allowed to see: everything except internal staff notes.
     *
     * @return HasMany<TicketReply, $this>
     */
    public function publicReplies(): HasMany
    {
        return $this->replies()->where('is_internal_note', false);
    }

    /** @param Builder<self> $query */
    public function scopeNeedsAttention(Builder $query): void
    {
        $query->whereIn('status', self::needsAttentionStatuses());
    }

    /**
     * Statuses that mean a customer is waiting on us.
     *
     * A named list as well as a scope: the admin table's tab filter is handed an untyped
     * query builder that cannot reach the scope, and duplicating the definition there is
     * how the two drift apart.
     *
     * @return array<int, string>
     */
    public static function needsAttentionStatuses(): array
    {
        return [TicketStatus::Open->value, TicketStatus::CustomerReply->value];
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }

    /** Hours since opening, used by the SLA column. */
    public function ageInHours(): int
    {
        return (int) $this->created_at->diffInHours(now());
    }

    public function isBreachingSla(): bool
    {
        if ($this->first_responded_at !== null || $this->isClosed()) {
            return false;
        }

        return $this->ageInHours() > $this->priority->responseHours();
    }
}
