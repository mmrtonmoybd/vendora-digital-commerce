@props([
    'seoTitle' => null,
    'seoDescription' => null,
    'panelTitle' => null,
    'panelLead' => null,
    'heading' => null,
    'subheading' => null,
])
@php
  $general = $store->general();

  /* Every one of these screens has two doors into it: the front page, and checkout, which is
     account-only now. Somebody pushed here by the second door did not set out to open an
     account, so the defaults have to read as an explanation of why the pass needs a name on
     it — not as a welcome mat for a returning member. */
  $panelTitle ??= __('account.sign_in_panel_title');
  $panelLead ??= __('account.sign_in_panel_lead');
  $heading ??= __('account.sign_in');
  $subheading ??= __('account.sign_in_sub');
@endphp
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
    href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,300..800&family=Hind+Siliguri:wght@400;500;600;700&display=swap"
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

<body class="bg-surface text-ink-900 antialiased min-h-screen flex flex-col overflow-x-clip" x-data="{ nav: false }">

  <main class="flex-1 grid lg:grid-cols-2 min-h-screen">
    {{--
      The counterfoil.

      The dark half is the issuing side of the document: ink ground under a guilloché, with
      the aurora drifting behind the line work rather than in front of it. That ordering is
      the whole point — engine-turned hairlines over light is what a certificate looks like,
      a soft glow on its own is what a hero image looks like.

      It is pinned to the top of the viewport at desktop width, so a long form (registration
      runs to eight fields) scrolls past terms that stay where the reader left them.
    --}}
    <section
      class="hero-ground relative hidden lg:flex lg:sticky lg:top-0 lg:self-start lg:h-screen flex-col justify-between text-white p-12 overflow-hidden">
      <span class="aurora"></span>
      <span class="guilloche absolute inset-0 pointer-events-none"></span>

      <a href="{{ route('home') }}" class="group relative flex items-center gap-2.5" aria-label="{{ $general->site_name }}">
        <span class="grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-brand-600 overflow-hidden sheen">
          <x-store.ui name="layers" class="w-[18px] h-[18px]" stroke-width="2.2" /></span>
        <span class="font-display text-[21px] font-extrabold leading-none select-none">{{ $general->site_name }}</span>
      </a>

      <div class="relative z-10 max-w-md rise-in">
        <h2 class="h-page">{{ $panelTitle }}</h2>
        <p class="mt-3.5 text-lg leading-relaxed text-white/70 max-w-[46ch]">{{ $panelLead }}</p>

        <ul class="mt-9 space-y-4">
          @foreach ([['zap', __('account.perk_delivery')], ['wallet', __('account.perk_wallet')], ['gift', __('account.perk_referral')]] as [$icon, $label])
            <li class="flex items-center gap-3.5">
              <span class="grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-white/10 border border-white/15 text-brand-400 shrink-0">
                <x-store.ui :name="$icon" class="w-4 h-4" /></span>{{ $label }}
            </li>
          @endforeach
        </ul>

        {{-- The footnote a permit carries under its terms. It is here because most people who
             land on this page arrived from checkout, and the one thing they need to know is
             that the email they sign in with is the email the pass is issued to. --}}
        <p class="mt-9 border-l-2 border-white/20 pl-4 text-sm leading-relaxed text-white/55">{{ __('account.panel_checkout_note') }}</p>
      </div>

      <div class="relative flex items-center justify-between text-sm text-white/50">
        <p>{{ $general->copyright_text }}</p>
        <p class="flex items-center gap-2"><span class="live-dot"></span>{{ __('store.secure_checkout') }}</p>
      </div>

      <span class="absolute -right-28 -bottom-28 w-[420px] h-[420px] rounded-full border border-white/10 spin-slow"></span>
      <span class="absolute -right-10 -bottom-40 w-[300px] h-[300px] rounded-full border border-white/10 spin-slow spin-reverse"></span>
    </section>

    <section class="flex flex-col">
      {{-- The narrow-screen masthead. It sticks, the same way the shop's own masthead does at
           every width: the way out of an account screen should never be a scroll away. --}}
      <div class="sticky top-0 z-50 hero-ground relative lg:hidden overflow-hidden border-b border-white/10">
        <span class="aurora"></span>
        <span class="guilloche absolute inset-0 pointer-events-none"></span>
        <div class="relative flex h-[60px] items-center justify-between px-5">
          <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white" aria-label="{{ $general->site_name }}">
            <span class="grid place-items-center w-8 h-8 rounded-[var(--radius-control)] bg-brand-600"><x-store.ui name="layers" class="w-4 h-4"
                stroke-width="2.2" /></span>
            <span class="font-display text-lg font-extrabold leading-none">{{ $general->site_name }}</span>
          </a>
          <a href="{{ route('home') }}" class="group inline-flex items-center gap-1.5 text-white/70 text-sm hover:text-white transition">
            <x-store.ui name="arrow-left" class="w-4 h-4 icon-nudge" />{{ __('account.back_to_shop') }}</a>
        </div>
      </div>

      <div class="flex-1 grid place-items-center p-5 sm:p-10">
        <div class="w-full max-w-md rise-in">
          <a href="{{ route('home') }}" class="group hidden lg:inline-flex items-center gap-1.5 text-sm text-ink-500 hover:text-brand-700 mb-7 transition">
            <x-store.ui name="arrow-left" class="w-4 h-4 icon-nudge" />{{ __('account.back_to_shop') }}</a>

          <h1 class="h-section">{{ $heading }}</h1>
          <p class="lede mt-2">{{ $subheading }}</p>

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
