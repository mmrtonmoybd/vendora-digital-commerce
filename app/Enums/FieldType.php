<?php

declare(strict_types=1);

namespace App\Enums;

/** The input types a product may ask for at checkout (player ID, account email, …). */
enum FieldType: string
{
    case Text = 'text';
    case Email = 'email';
    case Url = 'url';
    case Textarea = 'textarea';
    case Select = 'select';
    case Number = 'number';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Single line text',
            self::Email => 'Email address',
            self::Url => 'URL',
            self::Textarea => 'Paragraph',
            self::Select => 'Dropdown',
            self::Number => 'Number',
        };
    }

    public function color(): string
    {
        return 'gray';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text => 'heroicon-o-bars-3-bottom-left',
            self::Email => 'heroicon-o-envelope',
            self::Url => 'heroicon-o-link',
            self::Textarea => 'heroicon-o-document-text',
            self::Select => 'heroicon-o-chevron-up-down',
            self::Number => 'heroicon-o-hashtag',
        };
    }

    /** The Laravel validation rule fragment this type implies. */
    public function validationRule(): string
    {
        return match ($this) {
            self::Email => 'email:rfc',
            self::Url => 'url',
            self::Number => 'numeric',
            default => 'string',
        };
    }

    public function needsOptions(): bool
    {
        return $this === self::Select;
    }
}
