<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Taka only until an administrator turns the dollar on. The shop settles in BDT, so
        // the base is the honest default and anything else is a convenience on top of it.
        $this->migrator->add('currency.enabled_currencies', ['BDT']);
        $this->migrator->add('currency.default_currency', 'BDT');

        // 1 USD ≈ ৳120.00, stored as paisa. An administrator keeps this current.
        $this->migrator->add('currency.rates', ['USD' => 12000]);

        $this->migrator->add('currency.show_switcher', true);
        $this->migrator->add('currency.show_settlement_note', true);
    }
};
