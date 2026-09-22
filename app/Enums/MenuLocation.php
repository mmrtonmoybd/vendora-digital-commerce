<?php

declare(strict_types=1);

namespace App\Enums;

enum MenuLocation: string
{
    case Header = 'header';
    case Mobile = 'mobile';
    case Footer1 = 'footer_1';
    case Footer2 = 'footer_2';
    case Footer3 = 'footer_3';
    case Footer4 = 'footer_4';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Header navigation',
            self::Mobile => 'Mobile off-canvas',
            self::Footer1 => 'Footer column 1',
            self::Footer2 => 'Footer column 2',
            self::Footer3 => 'Footer column 3',
            self::Footer4 => 'Footer column 4',
        };
    }

    public function color(): string
    {
        return str_starts_with($this->value, 'footer') ? 'gray' : 'primary';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Header => 'heroicon-o-bars-3',
            self::Mobile => 'heroicon-o-device-phone-mobile',
            default => 'heroicon-o-bars-3-bottom-left',
        };
    }

    public function isFooter(): bool
    {
        return str_starts_with($this->value, 'footer');
    }
}
