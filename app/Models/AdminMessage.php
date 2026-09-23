<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageAudience;
use Database\Factories\AdminMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $subject
 * @property string $body
 * @property MessageAudience $audience
 * @property ?array $audience_meta
 * @property bool $send_email
 * @property int $recipient_count
 * @property ?int $sent_by
 * @property ?Carbon $sent_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
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

    /** @return BelongsTo<Admin, $this> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sent_by');
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }
}
