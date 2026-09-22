<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\PageTemplate;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Stat;
use App\Models\TeamMember;
use Illuminate\Contracts\View\View;

final class PageController extends Controller
{
    public function __invoke(Page $page): View
    {
        abort_unless($page->is_active, 404);

        return view($page->template->view(), $this->dataFor($page));
    }

    /** The story page is a CMS page with a fixed slug, so staff can still rewrite its body. */
    public function ourStory(): View
    {
        $page = Page::active()->where('template', PageTemplate::OurStory->value)->first()
            ?? Page::active()->where('slug', 'our-story')->firstOrFail();

        return view('pages.our-story', $this->dataFor($page));
    }

    /** @return array<string, mixed> */
    private function dataFor(Page $page): array
    {
        $data = ['page' => $page];

        if ($page->template === PageTemplate::OurStory) {
            $data['team'] = TeamMember::active()->get();
            $data['stats'] = Stat::active()->get();
        }

        return $data;
    }
}
