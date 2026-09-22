<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('homepage.hero_badge', 'Trusted by 25,000+ customers');
        $this->migrator->add('homepage.hero_title_before', 'Digital products,');
        $this->migrator->add('homepage.hero_title_script', 'delivered');
        $this->migrator->add('homepage.hero_title_after', 'in minutes.');
        $this->migrator->add('homepage.hero_subtitle', 'Subscriptions, license keys, game top-ups and gift cards — paid with bKash, Nagad or wallet and delivered straight to your inbox.');
        $this->migrator->add('homepage.hero_features', "Instant delivery\nReal human support\n100% secure");
        $this->migrator->add('homepage.hero_primary_text', 'Browse products');
        $this->migrator->add('homepage.hero_primary_url', '#categories');
        $this->migrator->add('homepage.hero_secondary_text', 'WhatsApp us');
        $this->migrator->add('homepage.hero_secondary_url', '');

        $this->migrator->add('homepage.categories_eyebrow', 'Shop by category');
        $this->migrator->add('homepage.categories_title', 'What are you looking for?');

        $this->migrator->add('homepage.testimonials_eyebrow', 'Loved by customers');
        $this->migrator->add('homepage.testimonials_title', 'Real orders. Real people.');
        $this->migrator->add('homepage.testimonials_subtitle', '4.9 average from 3,200+ reviews');

        $this->migrator->add('homepage.faq_eyebrow', 'FAQ');
        $this->migrator->add('homepage.faq_title', 'Questions, answered.');
        $this->migrator->add('homepage.faq_subtitle', "Can't find what you need? Our team replies in minutes.");
        $this->migrator->add('homepage.faq_button_text', 'Contact support');

        $this->migrator->add('homepage.cta_title', 'Earn wallet credit for every friend you bring.');
        $this->migrator->add('homepage.cta_subtitle', 'Create a free account, share your referral link and get rewarded when they order.');
        $this->migrator->add('homepage.cta_primary_text', 'Create free account');
        $this->migrator->add('homepage.cta_primary_url', '/register');
        $this->migrator->add('homepage.cta_secondary_text', 'How to buy?');
        $this->migrator->add('homepage.cta_secondary_url', '/page/how-to-buy');
    }
};
