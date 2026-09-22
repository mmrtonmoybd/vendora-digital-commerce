<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Settings\SeoSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * The machine-readable surface of the storefront.
 *
 * A sitemap index plus one child sitemap per content type: a single flat file grows past
 * the 50,000-URL limit once the catalogue does, and search consoles report per-sitemap
 * coverage, which is easier to act on.
 */
final class SeoController extends Controller
{
    public function __construct(private readonly SeoSettings $settings) {}

    public function sitemap(): Response
    {
        abort_unless($this->settings->sitemap_enabled, 404);

        $sections = ['pages', 'categories', 'products'];

        if ($this->settings->sitemap_include_blog) {
            $sections[] = 'posts';
        }

        $xml = view('seo.sitemap-index', ['sections' => $sections])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function sitemapSection(string $section): Response
    {
        abort_unless($this->settings->sitemap_enabled, 404);

        $urls = Cache::remember(
            "sitemap:{$section}",
            now()->addHours(6),
            fn (): array => $this->urlsFor($section),
        );

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = trim($this->settings->robots_txt)."\n\nSitemap: ".route('sitemap')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}> */
    private function urlsFor(string $section): array
    {
        return match ($section) {
            'pages' => $this->staticUrls() + Page::active()->get()
                ->map(fn (Page $page): array => $this->url(route('page.show', $page), $page->updated_at, 'monthly', '0.5'))
                ->all(),
            'categories' => Category::active()->get()
                ->map(fn (Category $c): array => $this->url(route('category.show', $c), $c->updated_at, 'daily', '0.8'))
                ->all(),
            'products' => Product::active()->get()
                ->map(fn (Product $p): array => $this->url(route('product.show', $p), $p->updated_at, 'daily', '0.9'))
                ->all(),
            'posts' => Post::published()->get()
                ->map(fn (Post $p): array => $this->url(route('blog.show', $p), $p->updated_at, 'weekly', '0.6'))
                ->all(),
            default => [],
        };
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
}
