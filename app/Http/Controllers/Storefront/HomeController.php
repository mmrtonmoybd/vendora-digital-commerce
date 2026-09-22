<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Testimonial;
use App\Services\StorefrontCache;
use App\Settings\ThemeSettings;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __construct(
        private readonly StorefrontCache $cache,
        private readonly ThemeSettings $theme,
    ) {}

    public function __invoke(): View
    {
        $perSection = $this->theme->home_products_per_section;

        $data = $this->cache->remember("home:{$perSection}", fn (): array => [
            'slides' => HeroSlide::active()->get(),
            'homeCategories' => Category::query()
                ->active()
                ->where('show_on_home', true)
                ->ordered()
                ->with([
                    'activeProducts' => fn ($q) => $q->with('category')->limit($perSection),
                ])
                ->withCount(['products as active_products_count' => fn ($q) => $q->where('is_active', true)])
                ->get(),
            'testimonials' => Testimonial::active()->get(),
            'faqs' => Faq::active()->where('group', 'general')->get(),
        ]);

        return view('pages.home', $data);
    }
}
