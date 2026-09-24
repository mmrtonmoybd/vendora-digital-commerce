@php
  $general = $store->general();
  $contact = $store->contact();
  $footerMenus = $store->footerMenus();
  $methods = $store->footerPaymentMethods();

  $socials = array_filter([
      'whatsapp' => filled($contact->whatsapp) ? 'https://wa.me/' . preg_replace('/\D/', '', $contact->whatsapp) : null,
      'telegram' => filled($contact->telegram) ? 'https://t.me/' . ltrim($contact->telegram, '@') : null,
      'facebook' => $contact->facebook,
      'youtube' => $contact->youtube,
      'discord' => $contact->discord,
  ]);
@endphp

{{--
  The footer.

  It closes the page on the same navy ground the masthead opened it on, so the shop reads
  as one continuous surface. The payment rails run as a marquee because there are usually
  more of them than fit a row, and a buyer scanning for their own rail should find it
  without a horizontal scrollbar.
--}}
<footer class="mt-auto relative hero-ground text-navy-300 overflow-hidden">
  <span class="aurora"></span>

  <div class="relative wrap pt-16 pb-10 grid gap-12 lg:grid-cols-[1.4fr_repeat(3,1fr)]">
    <div class="reveal">
      <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white" aria-label="{{ $general->site_name }}">
        <span class="grid place-items-center w-9 h-9 rounded-[10px] bg-brand-600"><x-store.ui name="layers" class="w-[18px] h-[18px]" stroke-width="2.2" /></span>
        <span class="font-display text-[21px] font-bold tracking-[-0.025em]">{{ $general->site_name }}</span>
      </a>

      <p class="mt-4 text-sm leading-relaxed max-w-xs text-white/60">{{ $store->st($general, 'tagline') }}</p>

      <div class="mt-6 space-y-2.5 text-sm">
        @if (filled($contact->phone))
          <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}" class="group flex items-center gap-2.5 hover:text-white transition">
            <x-store.ui name="phone" class="w-4 h-4 text-brand-400 icon-rise" />{{ $contact->phone }}</a>
        @endif
        @if (filled($contact->email))
          <a href="mailto:{{ $contact->email }}" class="group flex items-center gap-2.5 hover:text-white transition">
            <x-store.ui name="mail" class="w-4 h-4 text-brand-400 icon-rise" />{{ $contact->email }}</a>
        @endif
        <p class="flex items-center gap-2.5 text-white/60"><span class="live-dot"></span>{{ $store->st($general, 'support_hours') }}</p>
      </div>

      @if ($socials)
        <div class="mt-6 flex flex-wrap gap-2">
          @foreach ($socials as $network => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}" title="{{ ucfirst($network) }}"
              class="tap grid place-items-center w-10 h-10 rounded-[var(--radius-control)] bg-white/5 border border-white/10 text-white/70 hover:text-white hover:bg-brand-600 hover:border-brand-600 transition">
              <x-store.ui :name="$network" class="w-4 h-4" /></a>
          @endforeach
        </div>
      @endif
    </div>

    @foreach ($footerMenus as $menu)
      <div class="reveal">
        <h2 class="font-display font-semibold text-white mb-4">{{ $menu->t('column_title') ?: $menu->t('name') }}</h2>
        <ul class="space-y-3 text-sm">
          @foreach ($menu->items as $item)
            <li><a href="{{ $item->resolvedUrl() }}"@if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
                class="group inline-flex items-center gap-1.5 hover:text-white transition">{{ $item->t('label') }}
                <x-store.ui name="arrow-right" class="w-3.5 h-3.5 opacity-0 -translate-x-1 group-hover:opacity-60 group-hover:translate-x-0 transition" />
              </a></li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>

  @if ($methods->isNotEmpty())
    {{-- The rails this shop settles on, running continuously. Pauses on hover so a buyer
         can read the one they were looking for. --}}
    <div class="relative border-t border-white/10 py-5">
      <div class="wrap flex items-center gap-6">
        <span class="hidden sm:block shrink-0 text-xs text-white/40">{{ __('store.we_accept') }}</span>
        <div class="marquee flex-1">
          <div class="marquee-track gap-3">
            @foreach ($methods->concat($methods) as $method)
              <span
                class="shrink-0 inline-flex items-center gap-2 h-9 px-3.5 rounded-[var(--radius-control)] bg-white/5 border border-white/10 text-white text-xs font-semibold">
                <span class="w-2 h-2 rounded-full {{ $method->chip_color }}"></span>{{ $method->t('name') }}</span>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="relative border-t border-white/10">
    <div class="wrap py-6 flex flex-col sm:flex-row gap-3 items-center justify-between text-sm text-white/50">
      <p>{{ $store->st($general, 'copyright_text') }}</p>
      <p class="flex items-center gap-2"><x-store.ui name="shield" class="w-4 h-4 text-seat-600" />{{ __('store.secure_checkout') }}</p>
    </div>
  </div>
</footer>
