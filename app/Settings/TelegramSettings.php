<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class TelegramSettings extends Settings
{
    public bool $enabled;

    public string $bot_token;

    public string $chat_id;

    public bool $on_new_order;

    public bool $on_payment_proof;

    public bool $on_new_deposit;

    public bool $on_new_ticket;

    public bool $on_new_topup;

    public static function group(): string
    {
        return 'telegram';
    }

    public static function encrypted(): array
    {
        return ['bot_token'];
    }
}
