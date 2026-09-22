<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProblemReason;
use App\Enums\ProblemReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** @return BelongsTo<OrderItem, self> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Ticket, self> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<Admin, self> */
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
