<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class CategoryController extends Controller
{
    public function __invoke(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $sort = $request->string('sort')->toString();
        $inStockOnly = $request->boolean('in_stock');

        $products = Product::query()
            ->with(['category', 'platform'])
            ->active()
            ->with('category:id,name,slug')
            ->where(function (Builder $query) use ($category): void {
                // A parent category shows everything underneath it, not an empty page.
                $query->where('category_id', $category->getKey())
                    ->orWhereIn('category_id', $category->children()->pluck('id'));
            })
            ->when($inStockOnly, fn (Builder $q) => $q->inStock())
            ->tap(fn (Builder $q) => $this->applySort($q, $sort))
            ->paginate(12)
            ->withQueryString();

        return view('pages.category', [
            'category' => $category,
            'products' => $products,
            'siblings' => $this->chips($category),
            'sort' => $sort,
            'inStockOnly' => $inStockOnly,
        ]);
    }

    /**
     * The chip row: sub-categories if there are any, otherwise the siblings.
     *
     * @return Collection<int, Category>
     */
    private function chips(Category $category): Collection
    {
        $children = $category->children()->where('is_active', true)->get();

        if ($children->isNotEmpty()) {
            return $children;
        }

        return Category::query()
            ->active()
            ->where('parent_id', $category->parent_id)
            ->ordered()
            ->get();
    }

    /** @param Builder<Product> $query */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest' => $query->orderByDesc('id'),
            'popular' => $query->orderByDesc('total_sold'),
            default => $query->orderBy('sort_order')->orderByDesc('id'),
        };
    }
}
