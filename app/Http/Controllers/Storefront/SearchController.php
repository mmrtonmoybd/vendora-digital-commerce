<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = trim($request->string('q')->toString());

        $products = Product::query()
            ->active()
            ->with('category:id,name,slug')
            ->when($term !== '', fn (Builder $q) => $this->applySearch($q, $term))
            ->orderByDesc('total_sold')
            ->paginate(12)
            ->withQueryString();

        return view('pages.search', [
            'term' => $term,
            'products' => $products,
        ]);
    }

    /**
     * Full-text where the database supports it, LIKE where it does not.
     *
     * MATCH needs at least the server's minimum word length, so a one or two letter query
     * falls back to LIKE rather than silently returning nothing.
     *
     * @param  Builder<Product>  $query
     */
    private function applySearch(Builder $query, string $term): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true) && mb_strlen($term) >= 4) {
            $query->whereRaw(
                'MATCH (name, short, description) AGAINST (? IN BOOLEAN MODE)',
                [$this->booleanTerm($term)],
            );

            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'like', $like)
                ->orWhere('short', 'like', $like)
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));
        });
    }

    private function booleanTerm(string $term): string
    {
        $words = preg_split('/\s+/', $term) ?: [];

        return collect($words)
            ->map(fn (string $word): string => preg_replace('/[+\-><()~*"@]+/', '', $word) ?? '')
            ->filter()
            ->map(fn (string $word): string => $word.'*')
            ->implode(' ');
    }
}
