<?php

declare(strict_types=1);

namespace App\Enums;

enum AccessEventType: string
{
    case Granted = 'granted';
    case EmailChanged = 'email_changed';
    case CustomerConfirmed = 'customer_confirmed';
    case ProblemReported = 'problem_reported';
    case ReInvited = 're_invited';
    case Revoked = 'revoked';
    case Replaced = 'replaced';
    case Note = 'note';
    case Started = 'started';
    case Assigned = 'assigned';

    public function label(): string
    {
        return match ($this) {
            self::Granted => 'Access granted',
            self::EmailChanged => 'Delivery email changed',
            self::CustomerConfirmed => 'Customer confirmed access',
            self::ProblemReported => 'Problem reported',
            self::ReInvited => 'Re-invited',
            self::Revoked => 'Access revoked',
            self::Replaced => 'Access replaced',
            self::Note => 'Note',
            self::Started => 'Fulfilment started',
            self::Assigned => 'Assigned to staff',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Granted, self::CustomerConfirmed, self::Replaced, self::ReInvited => 'success',
            self::ProblemReported, self::Revoked => 'danger',
            self::EmailChanged => 'warning',
            default => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Granted => 'heroicon-o-key',
            self::EmailChanged => 'heroicon-o-envelope',
            self::CustomerConfirmed => 'heroicon-o-check-badge',
            self::ProblemReported => 'heroicon-o-exclamation-triangle',
            self::ReInvited => 'heroicon-o-arrow-path',
            self::Revoked => 'heroicon-o-no-symbol',
            self::Replaced => 'heroicon-o-arrows-right-left',
            self::Started => 'heroicon-o-play',
            self::Assigned => 'heroicon-o-user-plus',
            self::Note => 'heroicon-o-chat-bubble-left',
        };
    }
}
