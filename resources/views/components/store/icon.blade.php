@props([
    'name' => 'play',
    'strokeWidth' => 2,
])

{{-- The icon set is closed: every key comes from config/storefront.php, whose SVG bodies
     are copied verbatim from the template. An unknown key falls back rather than rendering
     an empty box. --}}
@php
    $body = config("storefront.icons.{$name}.svg") ?? config('storefront.icons.play.svg');
@endphp

<svg {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $body !!}</svg>
