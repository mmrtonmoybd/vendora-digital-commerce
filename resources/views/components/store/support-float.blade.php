@php
  $contact = $store->contact();
  $whatsapp = filled($contact->whatsapp)
      ? 'https://wa.me/' . preg_replace('/\D/', '', $contact->whatsapp) . '?text=' . rawurlencode((string) $contact->floating_support_message)
      : null;
  $telegram = filled($contact->telegram) ? 'https://t.me/' . ltrim($contact->telegram, '@') : null;
@endphp

<!-- Floating support (bottom-left) -->
<div class="fixed bottom-5 left-5 z-50" x-data="{ s: false }" @click.outside="s=false">
  <div x-show="s" x-cloak x-transition.origin.bottom.left class="mb-3 w-64 rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden">
    <div class="hero-ground p-4 text-white">
      <p class="font-display font-semibold">{{ __('store.need_a_hand') }}</p>
      <p class="text-xs text-white/80">{{ __('store.typical_reply') }}</p>
    </div>
    <div class="p-2">
      @if ($whatsapp)
        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center gap-3 p-3 rounded-xl hover:bg-brand-50"><span
            class="grid place-items-center w-9 h-9 rounded-full bg-emerald-600 text-white"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" />
            </svg></span><span class="text-sm font-semibold">{{ __('store.whatsapp_support') }}</span></a>
      @endif
      @if ($telegram)
        <a href="{{ $telegram }}" target="_blank" rel="noopener" class="flex items-center gap-3 p-3 rounded-xl hover:bg-brand-50"><span
            class="grid place-items-center w-9 h-9 rounded-full bg-sky-600 text-white"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="m22 2-7 20-4-9-9-4Z" />
              <path d="M22 2 11 13" />
            </svg></span><span class="text-sm font-semibold">{{ __('store.telegram_support') }}</span></a>
      @endif
      @if (filled($contact->phone))
        <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-brand-50"><span
            class="grid place-items-center w-9 h-9 rounded-full bg-brand-600 text-white"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path
                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg></span><span class="text-sm font-semibold">{{ __('store.call', ['number' => $contact->phone]) }}</span></a>
      @endif
    </div>
  </div>
  <button @click="s=!s"
    class="group flex items-center gap-2 h-12 pl-3 pr-5 rounded-full bg-brand-600 text-white font-semibold shadow-lg shadow-brand-900/30 hover:bg-brand-700 ring-4 ring-white">
    <span class="relative grid place-items-center w-7 h-7 rounded-full bg-white/20"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3" />
      </svg><span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-brand-600"></span></span>
    <span x-text="s ? @js(__('store.close')) : @js(__('store.support'))">{{ __('store.support') }}</span>
  </button>
</div>
