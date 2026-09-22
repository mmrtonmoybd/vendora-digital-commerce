<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Drops the cached sitemap sections so the next crawl sees today's catalogue.
 *
 * The sitemap is generated on request and cached for six hours; this clears it rather than
 * writing files, so there is nothing on disk to go stale or to serve after a rollback.
 */
final class GenerateSitemapCache extends Command
{
    protected $signature = 'sitemap:refresh';

    protected $description = 'Refresh the cached sitemap sections';

    public function handle(): int
    {
        foreach (['pages', 'categories', 'products', 'posts'] as $section) {
            Cache::forget("sitemap:{$section}");
        }

        $this->info('Sitemap cache cleared.');

        return self::SUCCESS;
    }
}
