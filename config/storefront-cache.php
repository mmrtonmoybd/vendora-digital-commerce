<?php

declare(strict_types=1);
use App\Http\Middleware\SetLiteSpeedVaryCookie;

return [
    'enabled' => env('STOREFRONT_CACHE_ENABLED', true),

    'laravel' => [
        'fresh_ttl' => env('STOREFRONT_CACHE_FRESH_TTL', 120),
        'stale_ttl' => env('STOREFRONT_CACHE_STALE_TTL', 600),
    ],

    'lscache' => [
        'enabled' => env('STOREFRONT_LSCACHE_ENABLED', true),
        'ttl' => env('STOREFRONT_LSCACHE_TTL', 300),
        'tag' => 'storefront',
        'vary_cookie' => SetLiteSpeedVaryCookie::COOKIE,
    ],
];
