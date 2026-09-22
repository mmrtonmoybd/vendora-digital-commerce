<?php

declare(strict_types=1);

namespace App\Enums;

enum MenuLinkType: string
{
    case Route = 'route';
    case Page = 'page';
    case Category = 'category';
    case Product = 'product';
    case Post = 'post';
    case Url = 'url';

    public function label(): string
    {
        return match ($this) {
            self::Route => 'Built-in page',
            self::Page => 'CMS page',
            self::Category => 'Category',
            self::Product => 'Product',
            self::Post => 'Blog post',
            self::Url => 'Custom URL',
        };
    }

    public function color(): string
    {
        return 'gray';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Route => 'heroicon-o-home',
            self::Page => 'heroicon-o-document',
            self::Category => 'heroicon-o-rectangle-stack',
            self::Product => 'heroicon-o-cube',
            self::Post => 'heroicon-o-newspaper',
            self::Url => 'heroicon-o-link',
        };
    }

    public function needsTarget(): bool
    {
        return in_array($this, [self::Page, self::Category, self::Product, self::Post], true);
    }
}
