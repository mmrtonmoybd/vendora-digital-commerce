<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the system's PDFs.
 *
 * mPDF rather than dompdf, for one reason: Bengali. dompdf draws glyphs in logical order
 * with no OpenType layout, so "ইনভয়েস" comes out of it as "ইনভয়সে" — the pre-base vowel
 * ে lands after the final consonant instead of before it, and conjuncts never form. For a
 * shop that prices in ৳ and sells to Bangladesh that is a broken document, not a cosmetic
 * nitpick. mPDF has its own OTL engine and shapes the script correctly.
 *
 * The face is Hind Siliguri, bundled in resources/fonts. It is the same family the
 * storefront loads, it carries a real Bold, and — unlike Noto Sans Bengali — mPDF can
 * actually parse its GPOS table. See resources/fonts/README.md.
 */
final class PdfService
{
    public const FONT_FAMILY = 'hindsiliguri';

    /**
     * Render a Blade view to a PDF the browser will download.
     *
     * @param  array<string, mixed>  $data
     */
    public static function download(string $view, array $data, string $filename): Response
    {
        return new Response(
            self::render($view, $data),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                // An invoice is per-customer. Nothing in front of the app may hold on to it.
                'Cache-Control' => 'no-store, private',
                'X-LiteSpeed-Cache-Control' => 'no-cache',
            ],
        );
    }

    /**
     * The raw PDF bytes, for mail attachments and tests.
     *
     * @param  array<string, mixed>  $data
     */
    public static function render(string $view, array $data, string $paper = 'A4'): string
    {
        return self::renderHtml(View::make($view, $data)->render(), $paper);
    }

    /** Render already-built HTML. Kept separate so callers can pre-render or cache it. */
    public static function renderHtml(string $html, string $paper = 'A4'): string
    {
        $mpdf = self::engine($paper);
        $mpdf->WriteHTML($html);

        return (string) $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * A configured engine.
     *
     * useOTL 0xFF turns on the full OpenType layout pass. Without it the Bengali faces load
     * but shape exactly as badly as dompdf did, which would make the whole swap pointless.
     */
    private static function engine(string $paper = 'A4'): Mpdf
    {
        return new Mpdf([
            'mode' => 'utf-8',
            'format' => $paper,
            'tempDir' => storage_path('framework/mpdf'),
            'fontDir' => array_merge(
                (new ConfigVariables)->getDefaults()['fontDir'],
                [resource_path('fonts')],
            ),
            'fontdata' => (new FontVariables)->getDefaults()['fontdata'] + [
                self::FONT_FAMILY => [
                    'R' => 'HindSiliguri-Regular.ttf',
                    'B' => 'HindSiliguri-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => self::FONT_FAMILY,
            'default_font_size' => 11,
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 16,
        ]);
    }

    /** Whether the bundled faces are actually on disk — checked by the deploy script. */
    public static function fontsAvailable(): bool
    {
        foreach (['HindSiliguri-Regular.ttf', 'HindSiliguri-Bold.ttf'] as $file) {
            if (! is_file(resource_path('fonts/'.$file))) {
                return false;
            }
        }

        return true;
    }
}
