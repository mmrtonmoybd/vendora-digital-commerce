<?php

declare(strict_types=1);

namespace App\Enums;

enum FaqGroup: string
{
    case General = 'general';
    case Product = 'product';
    case Payment = 'payment';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::General => 'primary',
            self::Product => 'info',
            self::Payment => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::General => 'heroicon-o-question-mark-circle',
            self::Product => 'heroicon-o-cube',
            self::Payment => 'heroicon-o-banknotes',
        };
    }
}
