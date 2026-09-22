<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('homepage.story_badge', 'since 2021');
        $this->migrator->add('homepage.story_eyebrow', 'Our story');
        $this->migrator->add('homepage.story_title', 'We started with one question: why is buying online so hard here?');
        $this->migrator->add('homepage.story_body', "Paying for a subscription or a game top-up shouldn't need a foreign card and a prayer. We let anyone pay with the mobile wallet they already use and get what they paid for in minutes — backed by people who answer when you message.");
        $this->migrator->add('homepage.team_eyebrow', 'The team');
        $this->migrator->add('homepage.team_title', 'People behind your orders');
    }
};
