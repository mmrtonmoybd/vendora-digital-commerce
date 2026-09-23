<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property ?string $phone
 * @property string $subject
 * @property string $message
 * @property ?string $ip
 * @property ContactMessageStatus $status
 * @property ?Carbon $replied_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class ContactMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'replied_at' => 'datetime',
        ];
    }
}
