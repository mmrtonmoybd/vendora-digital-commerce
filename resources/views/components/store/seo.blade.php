@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'type' => 'website',
    'robots' => null,
    'schema' => null,
])

@php
    $seo = app(\App\Settings\SeoSettings::class);
    $general = app(\App\Settings\GeneralSettings::class);

    $pageTitle = trim((string) ($title ?: $seo->default_title));
    $suffix = trim((string) $seo->title_suffix);
    $fullTitle = $suffix !== '' && ! str_contains($pageTitle, $suffix)
        ? $pageTitle.' '.$suffix
        : $pageTitle;

    $pageDescription = \Illuminate\Support\Str::of((string) ($description ?: $seo->default_description))
        ->stripTags()->squish()->limit(160)->value();

    $ogImage = $image
        ?: ($seo->og_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($seo->og_image) : null);

    $canonicalUrl = $canonical ?: url()->current();
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
@if (filled($store->st($seo, 'default_keywords')))
    <meta name="keywords" content="{{ $store->st($seo, 'default_keywords') }}">
@endif
<meta name="robots" content="{{ $robots ?: $seo->robots_default }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph: Facebook, Messenger, WhatsApp and LinkedIn all read these. --}}
<meta property="og:site_name" content="{{ $general->site_name }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:locale" content="en_US">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $fullTitle }}">
@endif
@if (filled($seo->facebook_app_id))
    <meta property="fb:app_id" content="{{ $seo->facebook_app_id }}">
@endif

{{-- Twitter/X. TikTok's in-app browser reads the Twitter card tags too. --}}
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
@if ($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif
@if (filled($seo->twitter_handle))
    <meta name="twitter:site" content="{{ \Illuminate\Support\Str::start($seo->twitter_handle, '@') }}">
    <meta name="twitter:creator" content="{{ \Illuminate\Support\Str::start($seo->twitter_handle, '@') }}">
@endif

{{-- Ownership verification. Each is only emitted once the admin has pasted a token. --}}
@if (filled($seo->google_site_verification))
    <meta name="google-site-verification" content="{{ $seo->google_site_verification }}">
@endif
@if (filled($seo->bing_site_verification))
    <meta name="msvalidate.01" content="{{ $seo->bing_site_verification }}">
@endif
@if (filled($seo->facebook_domain_verification))
    <meta name="facebook-domain-verification" content="{{ $seo->facebook_domain_verification }}">
@endif
@if (filled($seo->pinterest_verification))
    <meta name="p:domain_verify" content="{{ $seo->pinterest_verification }}">
@endif

{{-- Organisation and site search, on every page so crawlers always see them. --}}
@php
    $contact = app(\App\Settings\ContactSettings::class);

    $graph = [
        [
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => $general->site_name,
            'url' => url('/'),
            'description' => $store->st($general, 'tagline'),
            'logo' => $general->logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($general->logo) : null,
            'sameAs' => $seo->socialProfileUrls() ?: null,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => $contact->phone,
                'email' => $contact->email,
                'areaServed' => 'BD',
            ],
        ],
        [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => $general->site_name,
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];

    if ($schema !== null) {
        $graph[] = $schema;
    }

    $jsonLd = json_encode(
        ['@context' => 'https://schema.org', '@graph' => array_values($graph)],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
@endphp

<script type="application/ld+json">{!! $jsonLd !!}</script>
