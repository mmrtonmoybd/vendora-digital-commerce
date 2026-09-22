<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves payment screenshots and ticket attachments.
 *
 * They live on the private disk and are never linked directly. The signed URL proves the
 * link came from us, and the path is normalised so "../" cannot walk out of the directory.
 */
final class PrivateFileController extends Controller
{
    /** Directories a signed link is allowed to reach. */
    private const ALLOWED_ROOTS = ['deposits', 'orders', 'tickets', 'access-problems', 'topups'];

    public function __invoke(Request $request, string $path): StreamedResponse
    {
        $path = ltrim(str_replace(['..', "\0"], '', $path), '/');
        $root = explode('/', $path)[0] ?? '';

        abort_unless(in_array($root, self::ALLOWED_ROOTS, true), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
