<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Versioned storefront cache.
 *
 * Keys carry a version stamp rather than tags, so it works on the database cache store as
 * well as Redis. Bumping the version retires every storefront key at once, which is what an
 * observer does after any catalogue or content edit.
 */
final class StorefrontCache
{
    public const LSCACHE_TAG = 'storefront';

    public const VERSION_KEY = 'storefront:cache-version';

    public function remember(string $key, Closure $resolver): mixed
    {
        if (! config('storefront-cache.enabled', true)) {
            return $resolver();
        }

        return Cache::flexible(
            $this->versionedKey($key),
            [
                (int) config('storefront-cache.laravel.fresh_ttl', 300),
                (int) config('storefront-cache.laravel.stale_ttl', 3600),
            ],
            $resolver,
        );
    }

    /** Retire every storefront key, and tell LiteSpeed to drop its copies too. */
    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());

        if (config('storefront-cache.lscache.enabled', true) && class_exists(\Litespeed\LSCache\LSCache::class)) {
            \Litespeed\LSCache\LSCache::purgeTags([self::LSCACHE_TAG], false);
        }
    }

    public function currentVersion(): string
    {
        return (string) Cache::rememberForever(
            self::VERSION_KEY,
            static fn (): string => (string) Str::uuid(),
        );
    }

    private function versionedKey(string $key): string
    {
        return sprintf('storefront:%s:%s', $this->currentVersion(), hash('xxh128', $key));
    }
}
