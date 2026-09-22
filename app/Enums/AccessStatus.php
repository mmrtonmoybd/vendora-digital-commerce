<?php

declare(strict_types=1);

namespace App\Enums;

/** Where one purchased unit stands in the access-granting process. */
enum AccessStatus: string
{
    case Awaiting = 'awaiting';
    case InProgress = 'in_progress';
    case Granted = 'granted';
    case Confirmed = 'confirmed';
    case ProblemReported = 'problem_reported';
    case ReGranted = 're_granted';
    case Revoked = 'revoked';
    case Replaced = 'replaced';

    public function label(): string
    {
        return match ($this) {
            self::Awaiting => 'Waiting for access',
            self::InProgress => 'Access in progress',
            self::Granted => 'Access granted',
            self::Confirmed => 'Confirmed by customer',
            self::ProblemReported => 'Problem reported',
            self::ReGranted => 'Re-invited',
            self::Revoked => 'Access revoked',
            self::Replaced => 'Replaced',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Awaiting => 'warning',
            self::InProgress => 'info',
            self::Granted, self::ReGranted, self::Replaced => 'success',
            self::Confirmed => 'success',
            self::ProblemReported => 'danger',
            self::Revoked => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Awaiting => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-cog-6-tooth',
            self::Granted => 'heroicon-o-key',
            self::Confirmed => 'heroicon-o-check-badge',
            self::ProblemReported => 'heroicon-o-exclamation-triangle',
            self::ReGranted => 'heroicon-o-arrow-path',
            self::Revoked => 'heroicon-o-no-symbol',
            self::Replaced => 'heroicon-o-arrows-right-left',
        };
    }

    /** Still sitting in the fulfilment queue. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Awaiting, self::InProgress, self::ProblemReported], true);
    }

    /** Access exists on the platform right now. */
    public function isLive(): bool
    {
        return in_array($this, [self::Granted, self::Confirmed, self::ReGranted, self::Replaced], true);
    }

    /** The customer may still correct the delivery email themselves. */
    public function allowsCustomerEmailChange(): bool
    {
        return $this === self::Awaiting;
    }
}
