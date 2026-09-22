<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class ContactSettings extends Settings
{
    public ?string $phone;

    public ?string $email;

    public ?string $whatsapp;

    public ?string $telegram;

    public ?string $facebook;

    public ?string $youtube;

    public ?string $discord;

    public ?string $address;

    public ?string $map_embed_url;

    /** whatsapp | telegram | ticket */
    public string $floating_support_target;

    public ?string $floating_support_message;

    public static function group(): string
    {
        return 'contact';
    }
}
