@props([
    'seoTitle' => null,
    'seoDescription' => null,
    'panelTitle' => 'Welcome back!',
    'panelLead' => 'Sign in to pay with your wallet, track orders and earn referral credit.',
    'heading' => 'Sign in',
    'subheading' => 'Good to see you again.',
])
<!DOCTYPE html>
<html class="no-js" lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-currency="{{ $store->commerce()->currency_symbol }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <x-store.seo :title="$seoTitle" :description="$seoDescription" robots="noindex,nofollow" />
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
  {!! $store->seo()->custom_head_html !!}
</head>

<body class="bg-surface text-navy-900 antialiased min-h-screen flex flex-col overflow-x-clip" x-data="{ nav: false }">

  <main class="flex-1 grid lg:grid-cols-2 min-h-screen">
    {{-- The panel beside the form. It is the shop's dark ground, carrying the three things
         an account is actually for rather than a stock photograph. --}}
    <section class="hero-ground relative hidden lg:flex flex-col justify-between text-white p-12 overflow-hidden">
      <span class="aurora"></span>

      <a href="{{ route('home') }}" class="relative flex items-center gap-2.5" aria-label="{{ $store->general()->site_name }}">
        <span class="grid place-items-center w-9 h-9 rounded-[10px] bg-brand-600"><x-store.ui name="layers" class="w-[18px] h-[18px]"
            stroke-width="2.2" /></span>
        <span class="font-display text-[21px] font-bold tracking-[-0.025em]">{{ $store->general()->site_name }}</span>
      </a>

      <div class="relative z-10 max-w-md rise-in">
        <h2 class="font-display text-[2.5rem] font-bold leading-[1.1] tracking-[-0.025em]">{{ $panelTitle }}</h2>
        <p class="mt-3.5 text-white/70 text-lg leading-relaxed">{{ $panelLead }}</p>

        <ul class="mt-9 space-y-4">
          @foreach ([['zap', __('account.perk_delivery')], ['wallet', __('account.perk_wallet')], ['gift', __('account.perk_referral')]] as [$icon, $label])
            <li class="flex items-center gap-3.5">
              <span class="grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-white/10 border border-white/15 text-brand-400 shrink-0">
                <x-store.ui :name="$icon" class="w-4 h-4" /></span>{{ $label }}
            </li>
          @endforeach
        </ul>
      </div>

      <div class="relative flex items-center justify-between text-sm text-white/50">
        <p>{{ $store->general()->copyright_text }}</p>
        <p class="flex items-center gap-2"><span class="live-dot"></span>{{ __('store.secure_checkout') }}</p>
      </div>

      <span class="absolute -right-28 -bottom-28 w-[420px] h-[420px] rounded-full border border-white/10 spin-slow"></span>
      <span class="absolute -right-10 -bottom-40 w-[300px] h-[300px] rounded-full border border-white/10 spin-slow spin-reverse"></span>
    </section>

    <section class="flex flex-col">
      <div class="hero-ground relative lg:hidden px-5 py-5 flex items-center justify-between overflow-hidden">
        <span class="aurora"></span>
        <a href="{{ route('home') }}" class="relative flex items-center gap-2.5 text-white" aria-label="{{ $store->general()->site_name }}">
          <span class="grid place-items-center w-8 h-8 rounded-[9px] bg-brand-600"><x-store.ui name="layers" class="w-4 h-4" stroke-width="2.2" /></span>
          <span class="font-display text-lg font-bold tracking-[-0.02em]">{{ $store->general()->site_name }}</span>
        </a>
        <a href="{{ route('home') }}" class="group relative inline-flex items-center gap-1.5 text-white/70 text-sm hover:text-white transition">
          <x-store.ui name="arrow-left" class="w-4 h-4 icon-nudge" />{{ __('account.back_to_shop') }}</a>
      </div>

      <div class="flex-1 grid place-items-center p-5 sm:p-10">
        <div class="w-full max-w-md rise-in">
          <a href="{{ route('home') }}" class="group hidden lg:inline-flex items-center gap-1.5 text-sm text-navy-500 hover:text-brand-700 mb-7 transition">
            <x-store.ui name="arrow-left" class="w-4 h-4 icon-nudge" />{{ __('account.back_to_shop') }}</a>

          <h1 class="font-display text-[2rem] font-bold tracking-[-0.025em] text-navy-900">{{ $heading }}</h1>
          <p class="mt-2 text-navy-500">{{ $subheading }}</p>

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
