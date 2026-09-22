<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\SeoSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Search engines, social cards and advertising pixels.
 *
 * The raw HTML fields are deliberately unescaped when rendered: they exist so marketing can
 * paste a verification tag or a third-party snippet without a deploy. They are editable
 * only by staff who hold manage_settings.
 */
final class SeoSettingsPage extends BaseSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'SEO & tracking';

    public static function settingsClass(): string
    {
        return SeoSettings::class;
    }

    public function formFields(): array
    {
        return [
            Section::make('Defaults')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('default_title')->required()->maxLength(180),
                    TextInput::make('title_suffix')->maxLength(60)
                        ->helperText('Appended to every page title, e.g. " — Vendora".'),
                    Textarea::make('default_description')->rows(2)->required()->maxLength(300)->columnSpanFull(),
                    TextInput::make('default_keywords')->maxLength(300)->columnSpanFull(),
                    FileUpload::make('og_image')
                        ->label('Social share image')
                        ->image()->disk('public')->directory('seo')->visibility('public')
                        ->helperText('1200×630 works everywhere.'),
                    Select::make('robots_default')
                        ->label('Default robots directive')
                        ->options([
                            'index,follow' => 'index, follow (normal)',
                            'noindex,follow' => 'noindex, follow',
                            'noindex,nofollow' => 'noindex, nofollow (hide the whole shop)',
                        ])
                        ->required()
                        ->native(false),
                ]),

            Section::make('Advertising pixels')
                ->columns(2)
                ->columnSpanFull()
                ->description('Commerce events fire on all of these: view content, add to cart, begin checkout and purchase.')
                ->schema([
                    TextInput::make('meta_pixel_id')->label('Meta (Facebook) pixel ID'),
                    TextInput::make('tiktok_pixel_id')->label('TikTok pixel ID'),
                    TextInput::make('ga4_id')->label('Google Analytics 4 measurement ID')->placeholder('G-XXXXXXXXXX'),
                    TextInput::make('gtm_id')->label('Google Tag Manager ID')->placeholder('GTM-XXXXXXX')
                        ->helperText('When set, GA4 is left to GTM rather than loaded twice.'),
                    TextInput::make('google_ads_id')->label('Google Ads conversion ID')->placeholder('AW-XXXXXXXXX'),
                    TextInput::make('google_ads_conversion_label')->label('Google Ads conversion label'),
                ]),

            Section::make('Site verification')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('google_site_verification')->label('Google'),
                    TextInput::make('bing_site_verification')->label('Bing'),
                    TextInput::make('facebook_domain_verification')->label('Meta domain'),
                    TextInput::make('pinterest_verification')->label('Pinterest'),
                ]),

            Section::make('Social identity')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('twitter_handle')->label('X / Twitter handle')->prefix('@'),
                    TextInput::make('facebook_app_id')->label('Facebook app ID'),
                    Textarea::make('social_profiles')
                        ->label('Other profiles')
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('One URL per line. Published as the organisation\'s sameAs list.'),
                ]),

            Section::make('Sitemap')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('sitemap_enabled')->label('Publish a sitemap'),
                    Toggle::make('sitemap_include_blog')->label('Include blog posts'),
                    Textarea::make('robots_txt')->label('robots.txt')->rows(5)->columnSpanFull()
                        ->helperText('The sitemap link is appended automatically.'),
                ]),

            Section::make('Raw code')
                ->columnSpanFull()
                ->collapsed()
                ->description('Injected verbatim into every storefront page. Anything pasted here runs for every visitor.')
                ->schema([
                    Textarea::make('custom_head_html')
                        ->label('Before </head>')
                        ->rows(6)
                        ->helperText('Verification tags, fonts, third-party scripts.'),
                    Textarea::make('custom_body_html')
                        ->label('Before </body>')
                        ->rows(6)
                        ->helperText('Chat widgets and anything that should load last.'),
                ]),
        ];
    }
}
