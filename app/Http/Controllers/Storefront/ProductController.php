<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;

final class ProductController extends Controller
{
    public function __invoke(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'platform', 'fields', 'faqs']);

        // Counted without touching updated_at, so a view never looks like an edit.
        Product::whereKey($product->getKey())->update(['views' => $product->views + 1]);

        $related = $product->related()->active()->with('category')->limit(4)->get();

        if ($related->isEmpty()) {
            $related = Product::query()
                ->active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->getKey())
                ->with('category')
                ->limit(4)
                ->get();
        }

        return view('pages.product', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
