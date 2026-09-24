@php
  $general = $store->general();
  $mobileMenu = $store->menu(\App\Enums\MenuLocation::Mobile);
  $mobileMenu = $mobileMenu->isNotEmpty() ? $mobileMenu : $store->menu(\App\Enums\MenuLocation::Header);
@endphp

{{--
  The off-canvas drawer.

  Same vocabulary as the desktop header, stacked: identity, then who you are, then where you
  can go, then the two things you came to do.

  The nav row on the masthead is sticky at every width, so the drawer's own masthead is built
  to the same measure — the same 68px row on the same ink under the same guilloché, ruled off
  with the same hairline. Identity, account and search are pinned the way the sticky row is;
  only the list of destinations scrolls.
--}}
<div x-show="nav" x-cloak class="fixed inset-0 z-[70] lg:hidden" @keydown.escape.window="nav=false">
  <div x-show="nav" x-transition.opacity class="absolute inset-0 bg-ink-950/70 backdrop-blur-sm" @click="nav=false"></div>

  <aside x-show="nav" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full"
    x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full"
    class="absolute right-0 top-0 h-full w-[86%] max-w-sm bg-white text-ink-900 flex flex-col">

    <div class="hero-ground relative shrink-0 h-[68px] px-4 flex items-center justify-between gap-3 border-b border-white/10 text-white overflow-hidden">
      <span class="aurora"></span>
      <span class="guilloche absolute inset-0"></span>
      <a href="{{ route('home') }}" class="group relative flex items-center gap-2.5" aria-label="{{ __('store.site_home', ['site' => $general->site_name]) }}">
        <span class="grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-brand-600 overflow-hidden sheen">
          <x-store.ui name="layers" class="w-[18px] h-[18px]" stroke-width="2.2" /></span>
        <span class="font-display text-[19px] font-extrabold leading-none">{{ $general->site_name }}</span>
      </a>
      <button @click="nav=false"
        class="tap relative grid place-items-center w-10 h-10 rounded-[var(--radius-control)] border border-white/15 text-white/70 hover:text-white hover:border-white/35 transition"
        aria-label="{{ __('store.close_menu') }}"><x-store.ui name="close" class="w-5 h-5" /></button>
    </div>

    <div class="shrink-0 p-4 border-b border-ink-100">
      <a href="{{ route('login') }}" data-guest-only class="btn btn-primary btn-block sheen">
        <x-store.ui name="user" class="w-4 h-4" />{{ __('store.sign_in_register') }}</a>
      <div data-auth-only class="flex items-center gap-3 p-3 rounded-[var(--radius-panel)] bg-ink-50 border border-ink-100">
        <span class="grid place-items-center w-10 h-10 rounded-full bg-brand-600 text-white shrink-0"><x-store.ui name="user" class="w-4 h-4" /></span>
        <div class="flex-1 min-w-0">
          <p data-user-name class="font-display font-bold text-sm truncate"></p>
          {{-- The balance is a fact about the account, so it is ruled like every other fact
               on the site rather than written as a sentence. --}}
          <dl class="kv py-0 text-xs">
            <dt>{{ __('store.wallet') }}</dt>
            <dd data-user-balance class="tnum text-brand-700"></dd>
          </dl>
        </div>
      </div>
    </div>

    <form action="{{ route('search') }}" class="shrink-0 p-4 pb-2">
      <label class="relative block">
        <x-store.ui name="search" class="field-affix w-4 h-4" />
        <input name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('store.search_placeholder') }}" class="field field-icon">
      </label>
    </form>

    <nav class="p-2 flex-1 overflow-y-auto">
      @foreach ($mobileMenu as $item)
        <a href="{{ $item->resolvedUrl() }}"@if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
          class="tap group flex items-center justify-between gap-3 px-4 py-3 rounded-[var(--radius-control)] hover:bg-ink-50 font-medium">{{ $item->t('label') }}
          <x-store.ui name="arrow-right" class="w-4 h-4 shrink-0 text-brand-600 icon-nudge" /></a>
      @endforeach
      <x-store.language-switcher variant="panel" />
      <x-store.currency-switcher variant="panel" />
    </nav>

    <div class="shrink-0 p-4 border-t border-ink-100 grid grid-cols-2 gap-2">
      <a href="{{ route('cart') }}" class="btn btn-dark"><x-store.ui name="cart" class="w-4 h-4" />{{ __('store.my_cart') }} (<span data-cart-count
          class="tnum">0</span>)</a>
      <form method="POST" action="{{ route('logout') }}" data-auth-only>@csrf<button type="submit" class="btn btn-quiet btn-block">
          <x-store.ui name="logout" class="w-4 h-4" />{{ __('store.log_out') }}</button></form>
      <a href="{{ route('home') }}" data-guest-only class="btn btn-quiet">{{ __('store.home') }}</a>
    </div>
  </aside>
</div>
