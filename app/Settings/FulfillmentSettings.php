<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class FulfillmentSettings extends Settings
{
    /** Hand a paid order to a member of staff automatically. */
    public bool $auto_assign_enabled;

    /** Days of silence after which the system marks an item confirmed. */
    public int $auto_confirm_days;

    /** Also email the account address when it differs from the delivery email. */
    public bool $notify_account_email;

    /** Re-invite requests a customer may make per item per day. */
    public int $reinvite_daily_limit;

    /** Default shown in the buy modal under the email field. */
    public string $delivery_email_help;

    public static function group(): string
    {
        return 'fulfillment';
    }
}
