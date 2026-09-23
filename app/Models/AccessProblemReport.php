<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProblemReason;
use App\Enums\ProblemReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $order_item_id
 * @property ?int $user_id
 * @property ?string $guest_email
 * @property ProblemReason $reason
 * @property ?string $message
 * @property ?string $screenshot
 * @property ProblemReportStatus $status
 * @property ?string $resolution_note
 * @property ?int $ticket_id
 * @property ?int $resolved_by
 * @property ?Carbon $resolved_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class AccessProblemReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'reason' => ProblemReason::class,
            'status' => ProblemReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', ProblemReportStatus::Open->value);
    }
}
