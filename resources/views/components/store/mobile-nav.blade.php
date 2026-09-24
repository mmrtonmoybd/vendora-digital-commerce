@php
  $general = $store->general();
  $mobileMenu = $store->menu(\App\Enums\MenuLocation::Mobile);
  $mobileMenu = $mobileMenu->isNotEmpty() ? $mobileMenu : $store->menu(\App\Enums\MenuLocation::Header);
@endphp

{{-- The off-canvas drawer. Same vocabulary as the desktop header, stacked: identity, then
     who you are, then where you can go, then the two things you came to do. --}}
<div x-show="nav" x-cloak class="fixed inset-0 z-[70] lg:hidden" @keydown.escape.window="nav=false">
  <div x-show="nav" x-transition.opacity class="absolute inset-0 bg-navy-950/70 backdrop-blur-sm" @click="nav=false"></div>

  <aside x-show="nav" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full"
    x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full"
    class="absolute right-0 top-0 h-full w-[86%] max-w-sm bg-white text-navy-900 flex flex-col">

    <div class="hero-ground relative px-5 py-5 flex items-center justify-between text-white overflow-hidden">
      <span class="aurora"></span>
      <a href="{{ route('home') }}" class="relative flex items-center gap-2.5" aria-label="{{ __('store.site_home', ['site' => $general->site_name]) }}">
        <span class="grid place-items-center w-9 h-9 rounded-[10px] bg-brand-600"><x-store.ui name="layers" class="w-[18px] h-[18px]"
            stroke-width="2.2" /></span>
        <span class="font-display text-lg font-bold tracking-[-0.02em]">{{ $general->site_name }}</span>
      </a>
      <button @click="nav=false" class="tap relative grid place-items-center w-9 h-9 rounded-full bg-white/15" aria-label="{{ __('store.close_menu') }}">
        <x-store.ui name="close" class="w-5 h-5" /></button>
    </div>

    <div class="p-4 border-b border-navy-100">
      <a href="{{ route('login') }}" data-guest-only class="btn btn-primary btn-block sheen">
        <x-store.ui name="user" class="w-4 h-4" />{{ __('store.sign_in_register') }}</a>
      <div data-auth-only class="flex items-center gap-3 p-3 rounded-[var(--radius-panel)] bg-navy-50 border border-navy-100">
        <span class="grid place-items-center w-10 h-10 rounded-full bg-brand-600 text-white"><x-store.ui name="user" class="w-4 h-4" /></span>
        <div class="flex-1 min-w-0">
          <p data-user-name class="font-semibold text-sm truncate"></p>
          <p class="text-xs text-navy-500">{{ __('store.wallet') }}: <b data-user-balance class="tnum text-brand-700"></b></p>
        </div>
      </div>
    </div>

    <form action="{{ route('search') }}" class="p-4 pb-2">
      <label class="relative block">
        <x-store.ui name="search" class="field-affix w-4 h-4" />
        <input name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('store.search_placeholder') }}" class="field field-icon">
      </label>
    </form>

    <nav class="p-2 flex-1 overflow-y-auto">
      @foreach ($mobileMenu as $item)
        <a href="{{ $item->resolvedUrl() }}"@if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
          class="group flex items-center justify-between px-4 py-3 rounded-[var(--radius-control)] hover:bg-navy-50 font-medium">{{ $item->t('label') }}
          <x-store.ui name="arrow-right" class="w-4 h-4 text-brand-600 icon-nudge" /></a>
      @endforeach
      <x-store.language-switcher variant="panel" />
      <x-store.currency-switcher variant="panel" />
    </nav>

    <div class="p-4 border-t border-navy-100 grid grid-cols-2 gap-2">
      <a href="{{ route('cart') }}" class="btn btn-dark"><x-store.ui name="cart" class="w-4 h-4" />{{ __('store.my_cart') }} (<span data-cart-count
          class="tnum">0</span>)</a>
      <form method="POST" action="{{ route('logout') }}" data-auth-only>@csrf<button type="submit" class="btn btn-quiet btn-block">
          <x-store.ui name="logout" class="w-4 h-4" />{{ __('store.log_out') }}</button></form>
      <a href="{{ route('home') }}" data-guest-only class="btn btn-quiet">{{ __('store.home') }}</a>
    </div>
  </aside>
</div>
