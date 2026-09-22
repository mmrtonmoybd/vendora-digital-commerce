<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fulfillment.auto_assign_enabled', true);
        $this->migrator->add('fulfillment.auto_confirm_days', 3);
        $this->migrator->add('fulfillment.notify_account_email', true);
        $this->migrator->add('fulfillment.reinvite_daily_limit', 2);
        $this->migrator->add('fulfillment.delivery_email_help', 'Access will be given to this email.');
    }
};
