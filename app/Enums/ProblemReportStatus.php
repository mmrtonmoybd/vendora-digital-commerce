<?php

declare(strict_types=1);

namespace App\Enums;

enum ProblemReportStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::Resolved => 'success',
            self::Rejected => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-exclamation-triangle',
            self::Resolved => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }
}
