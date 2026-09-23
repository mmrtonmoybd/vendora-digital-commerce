<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\SlugRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Last stop before a 404: was this address something of ours that has since moved?
 *
 * Wired into the exception handler rather than a fallback route, because the usual case is
 * not "no route matched" — /product/{product:slug} matches fine, it is the model binding
 * that finds nothing. Either way the lookup only happens on the way to a 404, so a live
 * page never pays for it.
 */
final class SlugRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $this->resolve($request) ?? abort(404);
    }

    /** The redirect for this address, or null to let the 404 stand. */
    public function resolve(Request $request): ?RedirectResponse
    {
        $path = '/'.trim($request->path(), '/');

        $redirect = SlugRedirect::query()->where('from_path', $path)->first();

        if ($redirect === null) {
            return null;
        }

        // Counted without touching updated_at: this is traffic data, not an edit, and a
        // crawler sweeping old URLs should not make every row look freshly changed.
        SlugRedirect::query()->whereKey($redirect->getKey())->update([
            'hits' => $redirect->hits + 1,
        ]);

        return redirect()->to($redirect->to_path, $redirect->status_code ?: 301);
    }
}
