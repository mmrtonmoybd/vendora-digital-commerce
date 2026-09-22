<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Everything the crawlers and the social scrapers read.
 *
 * All of it is editable in the panel: pixel IDs, verification tokens and the default
 * social card all change from marketing's side without a deploy.
 */
final class SeoSettings extends Settings
{
    public string $default_title;

    /** Appended to every page title, e.g. " — Vendora". */
    public string $title_suffix;

    public string $default_description;

    public string $default_keywords;

    public ?string $og_image;

    // --- Analytics and advertising pixels -------------------------------------------------
    public string $ga4_id;

    public string $gtm_id;

    public string $meta_pixel_id;

    public string $tiktok_pixel_id;

    public string $google_ads_id;

    public string $google_ads_conversion_label;

    // --- Ownership verification -----------------------------------------------------------
    public string $google_site_verification;

    public string $bing_site_verification;

    public string $facebook_domain_verification;

    public string $pinterest_verification;

    // --- Social identity ------------------------------------------------------------------
    public string $twitter_handle;

    public string $facebook_app_id;

    /** Other profiles that belong to the same organisation, for the sameAs JSON-LD field. */
    public string $social_profiles;

    // --- Crawling -------------------------------------------------------------------------
    /** index,follow | noindex,nofollow | … */
    public string $robots_default;

    public string $robots_txt;

    public bool $sitemap_enabled;

    /** Include the storefront blog in the sitemap and in JSON-LD. */
    public bool $sitemap_include_blog;

    public string $custom_head_html;

    public string $custom_body_html;

    public static function group(): string
    {
        return 'seo';
    }

    /**
     * The organisation's other profiles, one URL per line.
     *
     * @return array<int, string>
     */
    public function socialProfileUrls(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[\r\n,]+/', $this->social_profiles) ?: [],
        )));
    }
}
