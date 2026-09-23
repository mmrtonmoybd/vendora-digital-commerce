<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageAudience;
use Database\Factories\AdminMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class AdminMessage extends Model
{
    /** @use HasFactory<AdminMessageFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'audience' => MessageAudience::class,
            'audience_meta' => 'array',
            'send_email' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(['read_at'])->withTimestamps();
    }

    /** @return BelongsTo<Admin, self> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sent_by');
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }
}
