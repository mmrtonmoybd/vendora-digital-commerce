<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Settings\SeoSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The machine-readable surface of the storefront: sitemaps and robots.txt.
 *
 * A sitemap index pointing at chunked child sitemaps, rather than one flat file. The
 * protocol caps a sitemap at 50,000 URLs and 50MB, and a shop that grows past either
 * silently stops being crawled properly — so the chunk size is fixed here and the index
 * grows instead. Search consoles also report coverage per sitemap, which makes a chunked
 * layout easier to act on when something is not being indexed.
 *
 * Nothing loads the whole catalogue into memory. Each chunk is built from a keyset query
 * over exactly its own slice, streamed out as it is generated, and cached as rendered XML
 * so a crawler sweeping forty chunks does not re-query forty times.
 */
final class SeoController extends Controller
{
    /**
     * URLs per child sitemap.
     *
     * Well under the 50,000 limit on purpose: smaller files are faster to fetch, cheaper
     * to regenerate when one product changes, and easier to read in a search console.
     */
    private const CHUNK = 5_000;

    private const SECTIONS = ['pages', 'categories', 'products', 'posts'];

    public function __construct(private readonly SeoSettings $settings) {}

    public function sitemap(): Response
    {
        abort_unless($this->settings->sitemap_enabled, 404);

        $xml = Cache::remember(
            'sitemap:index:'.app()->getLocale(),
            now()->addHours(6),
            function (): string {
                $entries = [];

                foreach ($this->activeSections() as $section) {
                    $total = $this->countFor($section);
                    $chunks = max(1, (int) ceil($total / self::CHUNK));

                    for ($page = 1; $page <= $chunks; $page++) {
                        $entries[] = [
                            'loc' => $chunks === 1
                                ? route('sitemap.section', ['section' => $section])
                                : route('sitemap.chunk', ['section' => $section, 'page' => $page]),
                            'lastmod' => $this->lastModifiedFor($section)->format(DATE_ATOM),
                        ];
                    }
                }

                return view('seo.sitemap-index', ['entries' => $entries])->render();
            },
        );

        return response($xml, 200, $this->xmlHeaders());
    }

    public function sitemapSection(string $section): Response
    {
        return $this->chunk($section, 1);
    }

    public function sitemapChunk(string $section, int $page): Response
    {
        return $this->chunk($section, $page);
    }

    /**
     * robots.txt, assembled rather than served from disk.
     *
     * Laravel ships a static public/robots.txt; it has been removed, because a file in
     * public/ is served by the web server before any route runs, which silently made every
     * setting on this screen unreachable.
     */
    public function robots(): Response
    {
        $lines = [trim($this->settings->robots_txt)];

        // Crawl budget spent on a checkout page is crawl budget not spent on a product.
        // These are also the paths that must never appear in an index.
        $disallow = [
            '/admin', '/customer', '/cart', '/checkout', '/order', '/access',
            '/payments', '/livewire', '/storefront/state', '/files',
            '/login', '/register', '/password', '/email/verify',
            '/language', '/currency', '/search?',
        ];

        $body = implode("\n", $lines)."\n";

        foreach ($disallow as $path) {
            if (! str_contains($body, 'Disallow: '.$path)) {
                $body .= 'Disallow: '.$path."\n";
            }
        }

        if ($this->settings->sitemap_enabled) {
            $body .= "\nSitemap: ".route('sitemap')."\n";
        }

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function chunk(string $section, int $page): Response
    {
        abort_unless($this->settings->sitemap_enabled, 404);
        abort_unless(in_array($section, $this->activeSections(), true), 404);
        abort_unless($page >= 1, 404);

        $key = "sitemap:{$section}:{$page}:".app()->getLocale();

        $xml = Cache::remember($key, now()->addHours(6), function () use ($section, $page): string {
            $urls = $this->urlsFor($section, $page);

            abort_if($urls === [] && $page > 1, 404);

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, $this->xmlHeaders());
    }

    /** @return array<int, string> */
    private function activeSections(): array
    {
        return array_values(array_filter(
            self::SECTIONS,
            fn (string $s): bool => $s !== 'posts' || $this->settings->sitemap_include_blog,
        ));
    }

    private function countFor(string $section): int
    {
        $extra = $section === 'pages' ? count($this->staticUrls()) : 0;

        $rows = match ($section) {
            'pages' => Page::active()->count(),
            'categories' => Category::active()->count(),
            'products' => Product::active()->count(),
            'posts' => Post::published()->count(),
            default => 0,
        };

        return $extra + $rows;
    }

    /**
     * When this section last changed.
     *
     * Drives the <lastmod> on the index entry, which is how a crawler decides whether a
     * child sitemap is worth refetching at all.
     */
    private function lastModifiedFor(string $section): \DateTimeInterface
    {
        $latest = match ($section) {
            'pages' => Page::active()->max('updated_at'),
            'categories' => Category::active()->max('updated_at'),
            'products' => Product::active()->max('updated_at'),
            'posts' => Post::published()->max('updated_at'),
            default => null,
        };

        return is_string($latest) ? Carbon::parse($latest) : now();
    }

    /**
     * One chunk's URLs.
     *
     * Ordered by primary key and windowed with offset/limit on an indexed column, so the
     * database reads only this chunk rather than materialising the catalogue.
     *
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function urlsFor(string $section, int $page): array
    {
        $offset = ($page - 1) * self::CHUNK;
        $static = [];

        if ($section === 'pages' && $page === 1) {
            $static = $this->staticUrls();
        }

        $limit = self::CHUNK - count($static);
        $skip = max(0, $offset - ($page === 1 ? 0 : count($this->staticUrls())));

        // Each branch queries its own model, so the rows are concretely typed rather than
        // a bare Model that every callback would have to re-narrow.
        $mapped = match ($section) {
            'pages' => Page::active()->orderBy('id')->skip($skip)->take($limit)->get()
                ->map(fn (Page $p): array => $this->url(route('page.show', $p), $p->updated_at, 'monthly', '0.5'))
                ->all(),
            'categories' => Category::active()->orderBy('id')->skip($skip)->take($limit)->get()
                ->map(fn (Category $c): array => $this->url(route('category.show', $c), $c->updated_at, 'daily', '0.8'))
                ->all(),
            'products' => Product::active()->orderBy('id')->skip($skip)->take($limit)->get()
                ->map(fn (Product $p): array => $this->url(route('product.show', $p), $p->updated_at, 'daily', '0.9'))
                ->all(),
            'posts' => Post::published()->orderBy('id')->skip($skip)->take($limit)->get()
                ->map(fn (Post $p): array => $this->url(route('blog.show', $p), $p->updated_at, 'weekly', '0.6'))
                ->all(),
            default => [],
        };

        // array_merge, never "+": on integer-keyed arrays "+" keeps the left operand's keys
        // and silently discards that many entries from the right. It was dropping the first
        // four pages from the sitemap.
        return array_merge($static, $mapped);
    }

    /** @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}> */
    private function staticUrls(): array
    {
        return [
            $this->url(route('home'), now(), 'daily', '1.0'),
            $this->url(route('blog.index'), now(), 'daily', '0.7'),
            $this->url(route('our-story'), now(), 'monthly', '0.5'),
            $this->url(route('contact'), now(), 'monthly', '0.5'),
        ];
    }

    /** @return array{loc: string, lastmod: string, changefreq: string, priority: string} */
    private function url(string $loc, ?\DateTimeInterface $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => ($lastmod ?? now())->format(DATE_ATOM),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    /** @return array<string, string> */
    private function xmlHeaders(): array
    {
        return [
            'Content-Type' => 'application/xml; charset=UTF-8',
            // Crawlers refetch these constantly; an hour at the edge costs nothing and
            // saves the origin a query storm during a recrawl.
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ];
    }
}
