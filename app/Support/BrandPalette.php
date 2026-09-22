<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builds the 50–900 brand scale from the single hex colour the administrator picks.
 *
 * Two paths. If the chosen colour is one of Tailwind's own hues — which the template's
 * default crimson #dc2626 is, being red-600 — the matching scale is used verbatim, so the
 * storefront renders exactly the colours it was designed against. Anything else is mixed
 * toward white and black at the ratios Tailwind's scales sit at, which gives a usable ramp
 * for a custom brand without pretending to be a colour science library.
 */
final class BrandPalette
{
    /** Mix ratios relative to the base colour: positive lightens, negative darkens. */
    private const STEPS = [
        50 => 0.95, 100 => 0.89, 200 => 0.78, 300 => 0.62, 400 => 0.38,
        500 => 0.16, 600 => 0.0, 700 => -0.16, 800 => -0.30, 900 => -0.42,
    ];

    /**
     * Tailwind's default palette, the source the template was drawn from.
     *
     * @var array<string, array<int, string>>
     */
    private const TAILWIND = [
        'red' => [50 => '#fef2f2', 100 => '#fee2e2', 200 => '#fecaca', 300 => '#fca5a5', 400 => '#f87171', 500 => '#ef4444', 600 => '#dc2626', 700 => '#b91c1c', 800 => '#991b1b', 900 => '#7f1d1d'],
        'rose' => [50 => '#fff1f2', 100 => '#ffe4e6', 200 => '#fecdd3', 300 => '#fda4af', 400 => '#fb7185', 500 => '#f43f5e', 600 => '#e11d48', 700 => '#be123c', 800 => '#9f1239', 900 => '#881337'],
        'orange' => [50 => '#fff7ed', 100 => '#ffedd5', 200 => '#fed7aa', 300 => '#fdba74', 400 => '#fb923c', 500 => '#f97316', 600 => '#ea580c', 700 => '#c2410c', 800 => '#9a3412', 900 => '#7c2d12'],
        'amber' => [50 => '#fffbeb', 100 => '#fef3c7', 200 => '#fde68a', 300 => '#fcd34d', 400 => '#fbbf24', 500 => '#f59e0b', 600 => '#d97706', 700 => '#b45309', 800 => '#92400e', 900 => '#78350f'],
        'emerald' => [50 => '#ecfdf5', 100 => '#d1fae5', 200 => '#a7f3d0', 300 => '#6ee7b7', 400 => '#34d399', 500 => '#10b981', 600 => '#059669', 700 => '#047857', 800 => '#065f46', 900 => '#064e3b'],
        'teal' => [50 => '#f0fdfa', 100 => '#ccfbf1', 200 => '#99f6e4', 300 => '#5eead4', 400 => '#2dd4bf', 500 => '#14b8a6', 600 => '#0d9488', 700 => '#0f766e', 800 => '#115e59', 900 => '#134e4a'],
        'sky' => [50 => '#f0f9ff', 100 => '#e0f2fe', 200 => '#bae6fd', 300 => '#7dd3fc', 400 => '#38bdf8', 500 => '#0ea5e9', 600 => '#0284c7', 700 => '#0369a1', 800 => '#075985', 900 => '#0c4a6e'],
        'blue' => [50 => '#eff6ff', 100 => '#dbeafe', 200 => '#bfdbfe', 300 => '#93c5fd', 400 => '#60a5fa', 500 => '#3b82f6', 600 => '#2563eb', 700 => '#1d4ed8', 800 => '#1e40af', 900 => '#1e3a8a'],
        'indigo' => [50 => '#eef2ff', 100 => '#e0e7ff', 200 => '#c7d2fe', 300 => '#a5b4fc', 400 => '#818cf8', 500 => '#6366f1', 600 => '#4f46e5', 700 => '#4338ca', 800 => '#3730a3', 900 => '#312e81'],
        'violet' => [50 => '#f5f3ff', 100 => '#ede9fe', 200 => '#ddd6fe', 300 => '#c4b5fd', 400 => '#a78bfa', 500 => '#8b5cf6', 600 => '#7c3aed', 700 => '#6d28d9', 800 => '#5b21b6', 900 => '#4c1d95'],
        'fuchsia' => [50 => '#fdf4ff', 100 => '#fae8ff', 200 => '#f5d0fe', 300 => '#f0abfc', 400 => '#e879f9', 500 => '#d946ef', 600 => '#c026d3', 700 => '#a21caf', 800 => '#86198f', 900 => '#701a75'],
        'pink' => [50 => '#fdf2f8', 100 => '#fce7f3', 200 => '#fbcfe8', 300 => '#f9a8d4', 400 => '#f472b6', 500 => '#ec4899', 600 => '#db2777', 700 => '#be185d', 800 => '#9d174d', 900 => '#831843'],
        'stone' => [50 => '#fafaf9', 100 => '#f5f5f4', 200 => '#e7e5e4', 300 => '#d6d3d1', 400 => '#a8a29e', 500 => '#78716c', 600 => '#57534e', 700 => '#44403c', 800 => '#292524', 900 => '#1c1917'],
    ];

    /**
     * Space-separated RGB triplets, keyed by shade.
     *
     * @return array<int, string>
     */
    public static function fromHex(string $hex): array
    {
        $hex = self::normalise($hex);

        $exact = self::exactScale($hex);

        if ($exact !== null) {
            return array_map(
                static fn (string $shade): string => implode(' ', self::toRgb($shade)),
                $exact,
            );
        }

        [$r, $g, $b] = self::toRgb($hex);
        $palette = [];

        foreach (self::STEPS as $shade => $ratio) {
            $palette[$shade] = implode(' ', [
                self::mix($r, $ratio),
                self::mix($g, $ratio),
                self::mix($b, $ratio),
            ]);
        }

        return $palette;
    }

    /** @return array{int, int, int} */
    public static function toRgb(string $hex): array
    {
        $hex = self::normalise($hex);

        return [
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
        ];
    }

    /**
     * The Tailwind scale this colour belongs to, if it is one of theirs.
     *
     * @return array<int, string>|null
     */
    private static function exactScale(string $hex): ?array
    {
        foreach (self::TAILWIND as $scale) {
            if (in_array($hex, $scale, true)) {
                return $scale;
            }
        }

        return null;
    }

    private static function normalise(string $hex): string
    {
        $hex = mb_strtolower(ltrim(trim($hex), '#'));

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-f]{6}$/', $hex)) {
            // Fall back to the template's crimson rather than rendering a broken palette.
            $hex = 'dc2626';
        }

        return '#'.$hex;
    }

    private static function mix(int $channel, float $ratio): int
    {
        $target = $ratio >= 0 ? 255 : 0;

        return (int) round($channel + ($target - $channel) * abs($ratio));
    }
}
