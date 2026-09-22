<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Read => 'gray',
            self::Replied => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'heroicon-o-envelope',
            self::Read => 'heroicon-o-envelope-open',
            self::Replied => 'heroicon-o-check-circle',
        };
    }
}
