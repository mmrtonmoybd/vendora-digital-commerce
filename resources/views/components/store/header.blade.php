@php
  $general = $store->general();
  $contact = $store->contact();
  $headerMenu = $store->menu(\App\Enums\MenuLocation::Header);
@endphp

{{--
  The masthead.

  Ink ground under a guilloché, with two slow brand lights drifting behind it, so the top of
  every page reads as the issuing desk rather than a printed banner.

  The strip above the nav carries the facts a buyer checks before trusting a shop — a number
  to ring, an address to write to, the hours somebody is actually there. The nav row itself
  is sticky at every width: this is a shop where people move between a category, a product
  and the cart constantly, and the cart count is the one figure they keep checking.
--}}
<header class="relative text-white isolate">
  <div class="hero-ground absolute inset-0 -z-10">
    <span class="aurora"></span>
    <span class="guilloche absolute inset-0"></span>
  </div>

  {{-- Contact strip. Scrolls away; it is reference, not navigation. --}}
  <div class="hidden md:block border-b border-white/10 text-[12.5px] text-white/60">
    <div class="wrap h-10 flex items-center justify-between">
      <div class="flex items-center gap-6">
        @if (filled($contact->phone))
          <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}" class="group flex items-center gap-2 hover:text-white transition">
            <x-store.ui name="phone" class="w-3.5 h-3.5 text-brand-400 icon-rise" />{{ $contact->phone }}</a>
        @endif
        @if (filled($contact->email))
          <a href="mailto:{{ $contact->email }}" class="group flex items-center gap-2 hover:text-white transition">
            <x-store.ui name="mail" class="w-3.5 h-3.5 text-brand-400 icon-rise" />{{ $contact->email }}</a>
        @endif
      </div>
      <div class="flex items-center gap-5">
        <x-store.language-switcher variant="strip" />
        <x-store.currency-switcher variant="strip" />
        <span class="flex items-center gap-2"><span class="live-dot"></span>{{ $store->st($general, 'support_hours') }}</span>
      </div>
    </div>
  </div>

  {{-- Navigation. Carries its own ink because it outlives the band it started in. --}}
  <div class="sticky top-0 z-50 bg-ink-950/85 backdrop-blur-md border-b border-white/10">
    <div class="wrap">
      <nav class="h-[68px] md:h-[72px] flex items-center gap-4">
        <a href="{{ route('home') }}" class="group flex items-center gap-2.5 shrink-0"
          aria-label="{{ __('store.site_home', ['site' => $general->site_name]) }}">
          <span class="relative grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-brand-600 text-white overflow-hidden sheen">
            <x-store.ui name="layers" class="w-[18px] h-[18px]" stroke-width="2.2" />
          </span>
          <span class="font-display text-[21px] font-extrabold leading-none select-none">{{ $general->site_name }}</span>
        </a>

        <div class="hidden lg:flex items-center gap-7 ml-8">
          @foreach ($headerMenu as $item)
            <a href="{{ $item->resolvedUrl() }}"@if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
              @if ($item->isActiveUrl()) aria-current="page" @endif
              class="underline-grow text-[14.5px] font-medium transition {{ $item->isActiveUrl() ? 'text-white' : 'text-white/65 hover:text-white' }}">{{ $item->t('label') }}</a>
          @endforeach
        </div>

        <div class="ml-auto flex items-center gap-2">
          <a href="{{ route('search') }}"
            class="tap hidden sm:grid place-items-center w-10 h-10 rounded-[var(--radius-control)] border border-white/15 text-white/70 hover:text-white hover:border-white/35 transition"
            aria-label="{{ __('store.search') }}"><x-store.ui name="search" class="w-[18px] h-[18px]" /></a>

          <a href="{{ route('login') }}" data-guest-only
            class="tap hidden sm:inline-flex items-center gap-2 h-10 px-4 rounded-[var(--radius-control)] border border-white/20 text-white font-medium text-sm hover:bg-white/10 transition">
            <x-store.ui name="user" class="w-4 h-4" />{{ __('store.sign_in') }}</a>

          <div data-auth-only class="relative hidden sm:block" x-data="{ open: false }" @click.outside="open=false">
            <button @click="open=!open"
              class="tap inline-flex items-center gap-2 h-10 pl-1.5 pr-3 rounded-[var(--radius-control)] border border-white/20 text-white text-sm font-medium hover:bg-white/10 transition">
              <span class="grid place-items-center w-7 h-7 rounded-[2px] bg-white/10"><x-store.ui name="user" class="w-3.5 h-3.5" /></span>
              {{ __('store.my_account') }}
              <span data-user-balance class="tnum px-2 py-0.5 rounded-[2px] bg-white/10 text-xs font-semibold"></span>
              <x-store.ui name="chevron-down" class="w-3.5 h-3.5 opacity-60 transition" ::class="open && 'rotate-180'" />
            </button>
            <div x-show="open" x-transition.origin.top.right x-cloak
              class="panel absolute right-0 mt-2 w-60 text-ink-900 p-2 z-50 shadow-xl">
              <div class="px-3 py-2.5 border-b border-ink-100 mb-1">
                <p data-user-name class="font-display font-bold text-sm"></p>
                <p data-user-email class="text-xs text-ink-500"></p>
              </div>
              <a href="{{ url('/customer') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-[var(--radius-control)] hover:bg-ink-50 text-sm">
                <x-store.ui name="grid" class="w-4 h-4 text-brand-600 icon-rise" />{{ __('store.dashboard') }}</a>
              <a href="{{ url('/customer/orders') }}"
                class="group flex items-center gap-2.5 px-3 py-2 rounded-[var(--radius-control)] hover:bg-ink-50 text-sm">
                <x-store.ui name="box" class="w-4 h-4 text-brand-600 icon-rise" />{{ __('store.my_orders') }}</a>
              <a href="{{ url('/customer/deposits/create') }}"
                class="group flex items-center gap-2.5 px-3 py-2 rounded-[var(--radius-control)] hover:bg-ink-50 text-sm">
                <x-store.ui name="wallet" class="w-4 h-4 text-brand-600 icon-rise" />{{ __('store.add_money') }}</a>
              <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"
                  class="group w-full flex items-center gap-2.5 px-3 py-2 rounded-[var(--radius-control)] hover:bg-ink-50 text-sm">
                  <x-store.ui name="logout" class="w-4 h-4 text-brand-600 icon-nudge" />{{ __('store.log_out') }}</button></form>
            </div>
          </div>

          <a href="{{ route('cart') }}" class="btn btn-primary sheen relative h-10">
            <x-store.ui name="cart" class="w-4 h-4" /><span class="hidden sm:inline">{{ __('store.my_cart') }}</span>
            <span data-cart-badge data-cart-count data-cart-empty
              class="tnum absolute -top-2 -right-2 min-w-[20px] h-5 px-1 grid place-items-center rounded-full bg-ink-900 text-white text-[11px] font-bold ring-2 ring-ink-950"></span>
          </a>

          <button @click="nav=true"
            class="tap lg:hidden grid place-items-center w-10 h-10 rounded-[var(--radius-control)] border border-white/15 text-white/70 hover:text-white transition"
            aria-label="{{ __('store.open_menu') }}"><x-store.ui name="menu" class="w-5 h-5" /></button>
        </div>
      </nav>
    </div>
  </div>

  <div class="wrap">{{ $slot }}</div>
</header>
