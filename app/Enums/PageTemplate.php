<?php

declare(strict_types=1);

namespace App\Enums;

/** Which storefront layout renders a CMS page. Each maps to a template file. */
enum PageTemplate: string
{
    case Default = 'default';
    case Contact = 'contact';
    case OurStory = 'our_story';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Standard page',
            self::Contact => 'Contact page',
            self::OurStory => 'Our story',
        };
    }

    public function color(): string
    {
        return 'gray';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Default => 'heroicon-o-document',
            self::Contact => 'heroicon-o-envelope',
            self::OurStory => 'heroicon-o-users',
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::Default => 'pages.page',
            self::Contact => 'pages.contact',
            self::OurStory => 'pages.our-story',
        };
    }
}
