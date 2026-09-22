@props([
    'seoTitle' => null,
    'seoDescription' => null,
    'panelTitle' => 'Welcome back!',
    'panelLead' => 'Sign in to pay with your wallet, track orders and earn referral credit.',
    'heading' => 'Sign in',
    'subheading' => 'Good to see you again.',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-currency="{{ $store->commerce()->currency_symbol }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<x-store.seo :title="$seoTitle" :description="$seoDescription" robots="noindex,nofollow" />
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
@vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
<x-store.brand-vars :brand-hex="$store->theme()->brand_hex" />
@if ($favicon = $store->general()->favicon)
<link rel="icon" href="{{ Storage::disk('public')->url($favicon) }}">
@endif
<x-store.pixels />
@livewireStyles
{!! $store->seo()->custom_head_html !!}
</head>
<body class="bg-surface text-ink antialiased min-h-screen flex flex-col overflow-x-clip" x-data="{ nav:false }" :class="nav && 'overflow-hidden'">

<main class="flex-1 grid lg:grid-cols-2 min-h-screen">
  <section class="relative hidden lg:flex flex-col justify-between hero-ground text-white p-12 overflow-hidden">
    <a href="{{ route('home') }}" class="font-script text-4xl text-white leading-none tracking-wide select-none" aria-label="{{ $store->general()->site_name }} home">{{ $store->general()->site_name }}<span class="text-amber-300">.</span></a>
    <div class="relative z-10 max-w-md">
      <h2 class="font-display text-4xl font-bold leading-tight">{{ $panelTitle }}</h2><p class="mt-3 text-white/85 text-lg">{{ $panelLead }}</p>
      <ul class="mt-8 space-y-3"><li class="flex items-center gap-3"><span class="grid place-items-center w-8 h-8 rounded-full bg-white/15"><svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>Instant delivery on most products</li><li class="flex items-center gap-3"><span class="grid place-items-center w-8 h-8 rounded-full bg-white/15"><svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg></span>One-click wallet checkout</li><li class="flex items-center gap-3"><span class="grid place-items-center w-8 h-8 rounded-full bg-white/15"><svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/></svg></span>Referral rewards for every friend</li></ul>
    </div>
    <svg class="absolute -right-24 -bottom-24 w-[520px] h-[520px] text-white/10" viewBox="0 0 200 200" aria-hidden="true"><circle cx="100" cy="100" r="98" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="100" cy="100" r="70" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="100" cy="100" r="42" fill="currentColor"/></svg>
    <p class="relative text-sm text-white/70">{{ $store->general()->copyright_text }}</p>
  </section>
  <section class="flex flex-col">
    <div class="lg:hidden hero-ground px-5 py-5 flex items-center justify-between"><a href="{{ route('home') }}" class="font-script text-3xl text-white leading-none tracking-wide select-none" aria-label="{{ $store->general()->site_name }} home">{{ $store->general()->site_name }}<span class="text-amber-300">.</span></a><a href="{{ route('home') }}" class="text-white/85 text-sm">&larr; Back to shop</a></div>
    <div class="flex-1 grid place-items-center p-5 sm:p-10">
      <div class="w-full max-w-md">
        <a href="{{ route('home') }}" class="hidden lg:inline-flex items-center gap-1 text-sm text-stone-500 hover:text-brand-700 mb-6">&larr; Back to shop</a>
        <h1 class="font-display text-3xl font-bold">{{ $heading }}</h1><p class="mt-1.5 text-stone-600">{{ $subheading }}</p>
        <div class="mt-8">
        {{ $slot }}
        </div>
      </div>
    </div>
  </section>
</main>

<x-store.support-float />

<x-store.toast />

@livewireScripts
{!! $store->seo()->custom_body_html !!}
</body>
</html>
