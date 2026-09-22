<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\StorefrontCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Any catalogue or content edit retires the storefront cache.
 *
 * Deliberately blunt: bumping one version key is cheaper than reasoning about which of the
 * header, the tiles, the home sections and the footer a given edit touched, and a stale
 * price on a live shop is not a trade worth making.
 */
final class CacheBustingObserver
{
    public function __construct(private readonly StorefrontCache $cache) {}

    public function saved(Model $model): void
    {
        $this->cache->invalidate();
    }

    public function deleted(Model $model): void
    {
        $this->cache->invalidate();
    }

    public function restored(Model $model): void
    {
        $this->cache->invalidate();
    }
}
