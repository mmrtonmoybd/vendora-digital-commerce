<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps old URLs working after somebody renames something.
 *
 * A slug is part of a public address. Editing it in the panel is a one-word change to the
 * person doing it, but it breaks every link already printed, shared or indexed. This
 * records the old address the moment the slug moves, and SlugRedirectController turns that
 * into a 301 — which is also what tells a search engine to carry the ranking across rather
 * than treat the new URL as a new page.
 *
 * Each model says how to turn a slug into a path; nothing here guesses at route names.
 *
 * @phpstan-type PathBuilder callable(string): string
 */
final class SlugHistoryObserver
{
    /**
     * How each model's slug becomes a path.
     *
     * @var array<class-string, string>
     */
    private const PATH_TEMPLATES = [
        Product::class => '/product/%s',
        Category::class => '/category/%s',
        Post::class => '/blog/%s',
        Page::class => '/page/%s',
    ];

    public function updated(Model $model): void
    {
        if (! $model->wasChanged('slug')) {
            return;
        }

        $template = self::PATH_TEMPLATES[$model::class] ?? null;

        if ($template === null) {
            return;
        }

        $old = (string) $model->getOriginal('slug');
        $new = (string) $model->getAttribute('slug');

        if ($old === '' || $old === $new) {
            return;
        }

        $from = sprintf($template, $old);
        $to = sprintf($template, $new);

        // Anything that already pointed at the old address now points at the new one, so a
        // slug renamed twice does not leave a chain of hops for a crawler to follow.
        SlugRedirect::query()->where('to_path', $from)->update(['to_path' => $to]);

        SlugRedirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => 301],
        );

        // Renaming back to a slug that used to redirect elsewhere would otherwise leave a
        // redirect pointing away from the live page.
        SlugRedirect::query()->where('from_path', $to)->delete();
    }
}
