<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Every fixed string on the home page.
 *
 * The template hard-codes this copy; here it is editable, so marketing can rewrite the
 * hero or the call to action without touching a Blade file.
 */
final class HomepageSettings extends Settings
{
    // Hero
    public string $hero_badge;

    public string $hero_title_before;

    /** Rendered in the script face, the way the template sets "delivered". */
    public string $hero_title_script;

    public string $hero_title_after;

    public string $hero_subtitle;

    /** One feature pill per line. */
    public string $hero_features;

    public string $hero_primary_text;

    public string $hero_primary_url;

    public string $hero_secondary_text;

    public string $hero_secondary_url;

    // Category block
    public string $categories_eyebrow;

    public string $categories_title;

    // Testimonials block
    public string $testimonials_eyebrow;

    public string $testimonials_title;

    public string $testimonials_subtitle;

    // FAQ block
    public string $faq_eyebrow;

    public string $faq_title;

    public string $faq_subtitle;

    public string $faq_button_text;

    // Closing call to action
    public string $cta_title;

    public string $cta_subtitle;

    public string $cta_primary_text;

    public string $cta_primary_url;

    public string $cta_secondary_text;

    public string $cta_secondary_url;

    // Our story page
    public string $story_badge;

    public string $story_eyebrow;

    public string $story_title;

    public string $story_body;

    public string $team_eyebrow;

    public string $team_title;

    public static function group(): string
    {
        return 'homepage';
    }

    /** @return array<int, string> */
    public function heroFeatures(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $this->hero_features) ?: [])));
    }
}
