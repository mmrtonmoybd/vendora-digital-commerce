<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->string('category')->toString();

        $posts = Post::query()
            ->published()
            ->with(['blogCategory', 'author'])
            ->when(
                $categorySlug !== '',
                fn (Builder $q) => $q->whereHas('blogCategory', fn (Builder $c) => $c->where('slug', $categorySlug)),
            )
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('pages.blog', [
            'posts' => $posts,
            'categories' => BlogCategory::active()->get(),
            'activeCategory' => $categorySlug,
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->is_published && $post->published_at?->isPast(), 404);

        $post->load(['blogCategory', 'author', 'tags']);

        Post::whereKey($post->getKey())->update(['views' => $post->views + 1]);

        $related = Post::query()
            ->published()
            ->where('blog_category_id', $post->blog_category_id)
            ->whereKeyNot($post->getKey())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('pages.blog-detail', [
            'post' => $post,
            'related' => $related,
        ]);
    }
}
