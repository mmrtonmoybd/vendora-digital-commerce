@php
  $general = $store->general();
  $contact = $store->contact();
  $headerMenu = $store->menu(\App\Enums\MenuLocation::Header);
@endphp

<!-- ================= HEADER ================= -->
<header class="relative text-white isolate">
  <div class="hero-ground absolute inset-0 -z-10"></div>
  <!-- top strip -->
  <div class="hidden md:block border-b border-white/10 text-[13px] text-white/80">
    <div class="max-w-7xl mx-auto px-4 h-9 flex items-center justify-between">
      <div class="flex items-center gap-5">
        @if (filled($contact->phone))
          <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}" class="flex items-center gap-1.5 hover:text-white"><svg class="w-3.5 h-3.5"
              viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path
                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg> {{ $contact->phone }}</a>
        @endif
        @if (filled($contact->email))
          <a href="mailto:{{ $contact->email }}" class="flex items-center gap-1.5 hover:text-white"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect width="20" height="16" x="2" y="4" rx="2" />
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
            </svg> {{ $contact->email }}</a>
        @endif
      </div>
      <div class="flex items-center gap-4"><x-store.language-switcher variant="strip" /><x-store.currency-switcher variant="strip" /><span
          class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />
          </svg> {{ __('store.instant_delivery') }}</span><span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
          </svg> {{ $store->st($general, 'support_hours') }}</span></div>
    </div>
  </div>

  <!-- nav -->
  <div class="max-w-7xl mx-auto px-4">
    <nav class="h-[72px] flex items-center gap-4">
      <a href="{{ route('home') }}" class="font-script text-3xl text-white leading-none tracking-wide select-none"
        aria-label="{{ __('store.site_home', ['site' => $general->site_name]) }}">{{ $general->site_name }}<span class="text-amber-300">.</span></a>
      <div class="hidden lg:flex items-center gap-1 ml-6">
        @foreach ($headerMenu as $item)
          <a href="{{ $item->resolvedUrl() }}"@if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
            class="px-3 py-2 rounded-full text-[15px] font-medium transition {{ $item->isActiveUrl() ? 'bg-white/15 text-white' : 'text-white/85 hover:text-white hover:bg-white/10' }}">{{ $item->t('label') }}</a>
        @endforeach
      </div>
      <div class="ml-auto flex items-center gap-2">
        <a href="{{ route('search') }}" class="hidden sm:grid place-items-center w-10 h-10 rounded-full bg-white/10 hover:bg-white/20"
          aria-label="{{ __('store.search') }}"><svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
          </svg></a>

        <a href="{{ route('login') }}" data-guest-only x-cloak
          class="hidden sm:inline-flex items-center gap-2 h-10 px-4 rounded-full bg-white text-brand-700 font-semibold text-sm hover:bg-brand-50"><svg
            class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            aria-hidden="true">
            <circle cx="12" cy="8" r="5" />
            <path d="M20 21a8 8 0 0 0-16 0" />
          </svg> {{ __('store.sign_in') }}</a>
        <div data-auth-only x-cloak class="relative hidden sm:block" x-data="{ open: false }" @click.outside="open=false">
          <button @click="open=!open"
            class="inline-flex items-center gap-2 h-10 pl-1.5 pr-3 rounded-full bg-white text-ink text-sm font-semibold hover:bg-brand-50">
            <span class="grid place-items-center w-7 h-7 rounded-full bg-brand-600 text-white"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="5" />
                <path d="M20 21a8 8 0 0 0-16 0" />
              </svg></span>
            {{ __('store.my_account') }}
            <span data-user-balance class="px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 text-xs font-bold"></span>
          </button>
          <div x-show="open" x-transition.origin.top.right x-cloak
            class="absolute right-0 mt-2 w-56 rounded-2xl bg-white text-ink shadow-xl ring-1 ring-black/5 p-2 z-50">
            <div class="px-3 py-2 border-b border-stone-100 mb-1">
              <p data-user-name class="font-semibold text-sm"></p>
              <p data-user-email class="text-xs text-stone-500"></p>
            </div>
            <a href="{{ url('/customer') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-brand-50 text-sm"><svg
                class="w-4 h-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <rect width="7" height="7" x="3" y="3" rx="1" />
                <rect width="7" height="7" x="14" y="3" rx="1" />
                <rect width="7" height="7" x="14" y="14" rx="1" />
                <rect width="7" height="7" x="3" y="14" rx="1" />
              </svg> {{ __('store.dashboard') }}</a>
            <a href="{{ url('/customer/orders') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-brand-50 text-sm"><svg
                class="w-4 h-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                <path d="m3.3 7 8.7 5 8.7-5" />
                <path d="M12 22V12" />
              </svg> {{ __('store.my_orders') }}</a>
            <a href="{{ url('/customer/deposits/create') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-brand-50 text-sm"><svg
                class="w-4 h-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1" />
                <path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4" />
              </svg> {{ __('store.add_money') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-brand-50 text-sm"><svg class="w-4 h-4 text-brand-600" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                  <polyline points="16 17 21 12 16 7" />
                  <line x1="21" x2="9" y1="12" y2="12" />
                </svg> {{ __('store.log_out') }}</button></form>
          </div>
        </div>

        <a href="{{ route('cart') }}"
          class="relative inline-flex items-center gap-2 h-10 px-4 rounded-full bg-amber-300 text-ink font-semibold text-sm hover:bg-amber-200">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            aria-hidden="true">
            <circle cx="8" cy="21" r="1" />
            <circle cx="19" cy="21" r="1" />
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
          </svg><span class="hidden sm:inline">{{ __('store.my_cart') }}</span>
          <span data-cart-badge data-cart-count x-cloak
            class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1 grid place-items-center rounded-full bg-ink text-white text-[11px] font-bold"></span>
        </a>
        <button @click="nav=true" class="lg:hidden grid place-items-center w-10 h-10 rounded-full bg-white/10 hover:bg-white/20"
          aria-label="{{ __('store.open_menu') }}"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="4" x2="20" y1="6" y2="6" />
            <line x1="4" x2="20" y1="12" y2="12" />
            <line x1="4" x2="20" y1="18" y2="18" />
          </svg></button>
      </div>
    </nav>
    {{ $slot }}
  </div>

  <!-- curved bottom -->
  <svg class="absolute -bottom-px left-0 w-full h-[60px] md:h-[90px] text-surface" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
    <path fill="currentColor" fill-opacity=".35" d="M0,70 C220,120 420,20 720,60 C1000,98 1220,30 1440,58 L1440,120 L0,120 Z" />
    <path fill="currentColor" d="M0,88 C260,130 480,40 760,78 C1040,116 1240,62 1440,84 L1440,120 L0,120 Z" />
  </svg>
</header>
