@php
    $general = $store->general();
    $mobileMenu = $store->menu(\App\Enums\MenuLocation::Mobile);
    $mobileMenu = $mobileMenu->isNotEmpty() ? $mobileMenu : $store->menu(\App\Enums\MenuLocation::Header);
@endphp

<!-- ================= MOBILE OFF-CANVAS ================= -->
<div x-show="nav" x-cloak class="fixed inset-0 z-[70] lg:hidden" @keydown.escape.window="nav=false">
  <div x-show="nav" x-transition.opacity class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @click="nav=false"></div>
  <aside x-show="nav" x-transition:enter="transition duration-300" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200" x-transition:leave-end="translate-x-full"
         class="absolute right-0 top-0 h-full w-[86%] max-w-sm bg-white text-ink flex flex-col">
    <div class="hero-ground px-5 py-5 flex items-center justify-between"><a href="{{ route('home') }}" class="font-script text-2xl text-white leading-none tracking-wide select-none" aria-label="{{ $general->site_name }} home">{{ $general->site_name }}<span class="text-amber-300">.</span></a>
      <button @click="nav=false" class="grid place-items-center w-9 h-9 rounded-full bg-white/15 text-white" aria-label="Close menu"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button></div>
    <div class="p-4 border-b border-stone-100">
      <a href="{{ route('login') }}" data-guest-only x-cloak class="flex items-center justify-center gap-2 h-11 rounded-xl bg-brand-600 text-white font-semibold"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg> Sign in / Register</a>
      <div data-auth-only x-cloak class="flex items-center gap-3 p-3 rounded-xl bg-brand-50">
        <span class="grid place-items-center w-10 h-10 rounded-full bg-brand-600 text-white"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg></span>
        <div class="flex-1"><p data-user-name class="font-semibold text-sm"></p><p class="text-xs text-stone-600">Wallet: <b data-user-balance class="text-brand-700"></b></p></div>
      </div>
    </div>
    <form action="{{ route('search') }}" class="p-4 pb-2"><label class="relative block"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></span>
      <input name="q" type="search" value="{{ request('q') }}" placeholder="Search products…" class="w-full h-11 pl-10 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></label></form>
    <nav class="p-2 flex-1 overflow-y-auto">@foreach ($mobileMenu as $item)<a href="{{ $item->resolvedUrl() }}"@if ($item->open_in_new_tab) target="_blank" rel="noopener"@endif class="flex items-center justify-between px-4 py-3 rounded-xl hover:bg-brand-50 font-medium">{{ $item->label }}<svg class="w-4 h-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
@endforeach</nav>
    <div class="p-4 border-t border-stone-100 grid grid-cols-2 gap-2">
      <a href="{{ route('cart') }}" class="h-11 flex items-center justify-center gap-1 rounded-xl bg-amber-300 font-semibold">My Cart (<span data-cart-count>0</span>)</a>
      <form method="POST" action="{{ route('logout') }}" data-auth-only x-cloak>@csrf<button type="submit" class="w-full h-11 rounded-xl border border-stone-200 font-semibold">Log out</button></form>
    </div>
  </aside>
</div>
