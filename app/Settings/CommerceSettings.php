<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class CommerceSettings extends Settings
{
    public string $currency_symbol;

    public string $currency_code;

    public string $order_prefix;

    public bool $guest_checkout_enabled;

    public int $auto_cancel_unpaid_minutes;

    public int $low_stock_threshold;

    public int $max_cart_items;

    /** Restore stock when an order is fully refunded. */
    public bool $restore_stock_on_refund;

    public static function group(): string
    {
        return 'commerce';
    }
}
