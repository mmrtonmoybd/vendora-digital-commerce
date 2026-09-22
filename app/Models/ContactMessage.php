<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Model;

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
