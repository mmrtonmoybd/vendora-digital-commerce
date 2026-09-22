<?php

declare(strict_types=1);

namespace App\Enums;

enum ProblemReason: string
{
    case InviteNotReceived = 'invite_not_received';
    case InviteExpired = 'invite_expired';
    case WrongEmail = 'wrong_email';
    case AccessRemoved = 'access_removed';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InviteNotReceived => "I didn't get the invitation",
            self::InviteExpired => 'The invitation expired',
            self::WrongEmail => 'It went to the wrong email',
            self::AccessRemoved => 'My access was removed',
            self::Other => 'Something else',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AccessRemoved => 'danger',
            self::WrongEmail => 'warning',
            default => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::InviteNotReceived => 'heroicon-o-inbox',
            self::InviteExpired => 'heroicon-o-clock',
            self::WrongEmail => 'heroicon-o-at-symbol',
            self::AccessRemoved => 'heroicon-o-no-symbol',
            self::Other => 'heroicon-o-question-mark-circle',
        };
    }

    /** Only a genuine loss of access is bound by the warranty window. */
    public function isWarrantyBound(): bool
    {
        return $this === self::AccessRemoved;
    }
}
