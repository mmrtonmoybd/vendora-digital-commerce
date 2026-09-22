<?php

declare(strict_types=1);

return [
    /** Edge Side Includes. Off: the storefront varies whole pages, not fragments. */
    'esi' => env('LSCACHE_ESI_ENABLED', false),

    /** Default TTL in seconds. Per-response headers override this. */
    'default_ttl' => env('LSCACHE_DEFAULT_TTL', 0),

    /** private | no-cache | public | no-vary */
    'default_cacheability' => env('LSCACHE_DEFAULT_CACHEABILITY', 'no-cache'),

    /** Never serve a signed-in customer a cached page. */
    'guest_only' => env('LSCACHE_GUEST_ONLY', true),
];
