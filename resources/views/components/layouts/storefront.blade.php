@props([
    'seoTitle' => null,
    'seoDescription' => null,
    'seoImage' => null,
    'seoCanonical' => null,
    'seoType' => 'website',
    'seoRobots' => null,
    'seoSchema' => null,
    'mainClass' => '',
])
<!DOCTYPE html>
<html class="no-js" lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-currency="{{ $store->commerce()->currency_symbol }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <x-store.seo
    :title="$seoTitle"
    :description="$seoDescription"
    :image="$seoImage"
    :canonical="$seoCanonical"
    :type="$seoType"
    :robots="$seoRobots"
    :schema="$seoSchema" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Sora:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
  <x-store.brand-vars :brand-hex="$store->theme()->brand_hex" />
  @if ($favicon = $store->general()->favicon)
    <link rel="icon" href="{{ Storage::disk('public')->url($favicon) }}">
  @endif
  <x-store.pixels />
  @livewireStyles
  {{-- Raw markup the administrator pastes in Settings → SEO. Deliberately unescaped: it is
     marketing script entered by a trusted operator, and escaping it would make the field
     useless. --}}
  {!! $store->seo()->custom_head_html !!}
</head>

<body class="bg-surface text-navy-900 antialiased min-h-screen flex flex-col overflow-x-clip" x-data="{ nav: false }" :class="nav && 'overflow-hidden'">
  @if (filled($store->seo()->gtm_id))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $store->seo()->gtm_id }}" height="0" width="0"
        style="display:none;visibility:hidden"></iframe></noscript>
  @endif

  <x-store.header>{{ $hero ?? '' }}</x-store.header>

  <x-store.mobile-nav />

  <main class="flex-1 {{ $mainClass }}">
    {{ $slot }}
  </main>

  <x-store.footer />

  <livewire:cart-actions />

  <x-store.support-float />

  <x-store.toast />

  @livewireScripts
  {!! $store->seo()->custom_body_html !!}
</body>

</html>
