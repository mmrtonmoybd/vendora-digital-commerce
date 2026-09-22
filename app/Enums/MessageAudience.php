<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageAudience: string
{
    case All = 'all';
    case SelectedUsers = 'selected_users';
    case BuyersOfProduct = 'buyers_of_product';
    case BuyersOfCategory = 'buyers_of_category';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All customers',
            self::SelectedUsers => 'Selected customers',
            self::BuyersOfProduct => 'Buyers of product(s)',
            self::BuyersOfCategory => 'Buyers of category(ies)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::All => 'primary',
            self::SelectedUsers => 'info',
            self::BuyersOfProduct => 'success',
            self::BuyersOfCategory => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::All => 'heroicon-o-megaphone',
            self::SelectedUsers => 'heroicon-o-user-group',
            self::BuyersOfProduct => 'heroicon-o-shopping-bag',
            self::BuyersOfCategory => 'heroicon-o-rectangle-stack',
        };
    }
}
