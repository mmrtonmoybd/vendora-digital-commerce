<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Full SEO: search engines, Meta, TikTok, Twitter/X, Pinterest and site verification. */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.title_suffix', '');
        $this->migrator->add('seo.default_keywords', '');

        $this->migrator->add('seo.gtm_id', '');
        $this->migrator->add('seo.tiktok_pixel_id', '');
        $this->migrator->add('seo.google_ads_id', '');
        $this->migrator->add('seo.google_ads_conversion_label', '');

        $this->migrator->add('seo.google_site_verification', '');
        $this->migrator->add('seo.bing_site_verification', '');
        $this->migrator->add('seo.facebook_domain_verification', '');
        $this->migrator->add('seo.pinterest_verification', '');

        $this->migrator->add('seo.twitter_handle', '');
        $this->migrator->add('seo.facebook_app_id', '');
        $this->migrator->add('seo.social_profiles', '');

        $this->migrator->add('seo.robots_default', 'index,follow');
        $this->migrator->add('seo.sitemap_enabled', true);
        $this->migrator->add('seo.sitemap_include_blog', true);
    }
};
