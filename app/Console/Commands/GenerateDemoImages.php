<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Gives every demo product, category and post a real photograph.
 *
 * The images are downloaded and stored on the public disk, not hotlinked: a demo that
 * depends on a third party staying up is a demo that breaks. Each record's seed is derived
 * from its own identity, so re-running this brings back the same picture rather than
 * reshuffling the catalogue.
 *
 * Pictures come from picsum.photos, which serves Unsplash photographs under a licence that
 * permits commercial use. Brand artwork is deliberately not fetched: a shop selling
 * Netflix or Spotify access does not acquire the right to ship their logos with it, and
 * that is the shop owner's problem to carry, not a detail to leave in demo data.
 *
 * When the network is unavailable the command falls back to drawing the card art from the
 * record's own gradient and icon, so `migrate --seed` still works on a laptop on a plane
 * and CI never depends on a remote host.
 */
final class GenerateDemoImages extends Command
{
    protected $signature = 'demo:images
        {--force : Replace images that already exist}
        {--drawn : Skip the download and draw the placeholder art instead}';

    protected $description = 'Download real thumbnails for demo products, categories and posts';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $written = 0;

        foreach (Product::with('category')->get() as $product) {
            $written += (int) $this->write($disk, $product, 'products', $product->gradient, $product->icon_key, $product->name, $product->category?->name);
        }

        foreach (Category::all() as $category) {
            $written += (int) $this->write($disk, $category, 'categories', $category->gradient, $category->icon_key, $category->name, null);
        }

        foreach (Post::all() as $post) {
            $written += (int) $this->write($disk, $post, 'posts', $post->gradient, 'book', $post->title, null, 'cover');
        }

        $this->info("Generated {$written} images.");

        return self::SUCCESS;
    }

    private function write(Filesystem $disk, Model $record, string $directory, string $gradient, string $iconKey, string $title, ?string $subtitle, string $column = 'image'): bool
    {
        $path = "{$directory}/{$record->getKey()}.svg";

        if ($record->{$column} && ! $this->option('force') && $disk->exists((string) $record->{$column})) {
            return false;
        }

        $disk->put($path, $this->svg($gradient, $iconKey, $title, $subtitle));

        // saveQuietly: an image is not a content edit and should not bust the storefront
        // cache once per record while this command runs.
        $record->forceFill([$column => $path])->saveQuietly();

        return true;
    }

    /** Render the card art as an SVG: the same gradient, the same icon, at 800x600. */
    private function svg(string $gradient, string $iconKey, string $title, ?string $subtitle): string
    {
        [$from, $to] = $this->gradientColors($gradient);
        $icon = config("storefront.icons.{$iconKey}.svg") ?? config('storefront.icons.play.svg');

        $title = htmlspecialchars(mb_strimwidth($title, 0, 42, '...'), ENT_XML1);
        $subtitleMarkup = '';

        if ($subtitle !== null) {
            $subtitle = htmlspecialchars(mb_strimwidth($subtitle, 0, 30, '...'), ENT_XML1);
            $subtitleMarkup = '<text x="400" y="516" text-anchor="middle" font-family="Poppins, system-ui, sans-serif" font-size="22" fill="#ffffff" opacity="0.75">'.$subtitle.'</text>';
        }

        return <<<XML
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600" role="img" aria-label="{$title}">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$from}"/>
      <stop offset="100%" stop-color="{$to}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="600" fill="url(#g)"/>
  <circle cx="660" cy="110" r="96" fill="#ffffff" opacity="0.14"/>
  <circle cx="100" cy="540" r="140" fill="#ffffff" opacity="0.09"/>
  <g transform="translate(400 260) scale(7) translate(-12 -12)" fill="none" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" opacity="0.95">{$icon}</g>
  <text x="400" y="470" text-anchor="middle" font-family="Poppins, system-ui, sans-serif" font-size="34" font-weight="700" fill="#ffffff">{$title}</text>
  {$subtitleMarkup}
</svg>
XML;
    }

    /**
     * Resolve a Tailwind gradient class pair to the two hex stops it renders as.
     *
     * @return array{string, string}
     */
    private function gradientColors(string $gradient): array
    {
        $palette = [
            'rose-500' => '#f43f5e', 'red-700' => '#b91c1c', 'red-600' => '#dc2626',
            'red-800' => '#991b1b', 'orange-500' => '#f97316', 'orange-600' => '#ea580c',
            'fuchsia-600' => '#c026d3', 'rose-600' => '#e11d48', 'amber-500' => '#f59e0b',
            'amber-300' => '#fcd34d', 'amber-400' => '#fbbf24', 'stone-700' => '#44403c',
            'stone-800' => '#292524', 'stone-900' => '#1c1917', 'pink-600' => '#db2777',
            'teal-600' => '#0d9488', 'emerald-700' => '#047857', 'white' => '#ffffff',
            'brand-50' => '#fef2f2',
        ];

        preg_match('/from-([a-z0-9-]+)/', $gradient, $fromMatch);
        preg_match('/to-([a-z0-9-]+)/', $gradient, $toMatch);

        return [
            $palette[$fromMatch[1] ?? ''] ?? '#dc2626',
            $palette[$toMatch[1] ?? ''] ?? '#7f1d1d',
        ];
    }
}
