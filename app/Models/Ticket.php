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

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Order, self> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Admin, self> */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /** @return HasMany<TicketReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }

    /** What the customer is allowed to see: everything except internal staff notes. */
    public function publicReplies(): HasMany
    {
        return $this->replies()->where('is_internal_note', false);
    }

    /** @param Builder<self> $query */
    public function scopeNeedsAttention(Builder $query): void
    {
        $query->whereIn('status', [TicketStatus::Open->value, TicketStatus::CustomerReply->value]);
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
