<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // English on by default with Bengali alongside it: the shop sells in Bangladesh and
        // prices in taka, so a Bengali-reading visitor should not have to go looking for a
        // language switch.
        $this->migrator->add('locale.enabled_locales', ['en', 'bn']);
        $this->migrator->add('locale.default_locale', 'en');
        $this->migrator->add('locale.auto_detect', true);
        $this->migrator->add('locale.show_switcher', true);
        $this->migrator->add('locale.apply_to_customer_panel', true);
        $this->migrator->add('locale.apply_to_admin_panel', false);
    }
};
