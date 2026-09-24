@php
  $contact = $store->contact();
  $whatsapp = filled($contact->whatsapp)
      ? 'https://wa.me/' . preg_replace('/\D/', '', $contact->whatsapp) . '?text=' . rawurlencode((string) $contact->floating_support_message)
      : null;
  $telegram = filled($contact->telegram) ? 'https://t.me/' . ltrim($contact->telegram, '@') : null;
@endphp

{{-- Support, always within reach. Access is granted by a person, so the way to reach that
     person is on screen at every step rather than filed under a contact page. --}}
<div class="fixed bottom-5 left-5 z-50" x-data="{ open: false }" @click.outside="open=false">
  <div x-show="open" x-cloak x-transition.origin.bottom.left class="panel mb-3 w-[17rem] overflow-hidden shadow-2xl">
    <div class="hero-ground relative p-4 text-white overflow-hidden">
      <span class="aurora"></span>
      <span class="guilloche absolute inset-0"></span>
      <p class="relative font-display font-bold">{{ __('store.need_a_hand') }}</p>
      <p class="relative mt-0.5 text-xs text-white/70 flex items-center gap-2"><span class="live-dot"></span>{{ __('store.typical_reply') }}</p>
    </div>
    <div class="p-2">
      @if ($whatsapp)
        <a href="{{ $whatsapp }}" target="_blank" rel="noopener"
          class="group flex items-center gap-3 p-3 rounded-[var(--radius-control)] hover:bg-ink-50 transition">
          <span class="grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-grant-600 text-white shrink-0">
            <x-store.ui name="whatsapp" class="w-4 h-4" /></span>
          <span class="text-sm font-semibold">{{ __('store.whatsapp_support') }}</span>
          <x-store.ui name="arrow-right" class="w-4 h-4 ml-auto text-ink-400 icon-nudge" /></a>
      @endif
      @if ($telegram)
        <a href="{{ $telegram }}" target="_blank" rel="noopener"
          class="group flex items-center gap-3 p-3 rounded-[var(--radius-control)] hover:bg-ink-50 transition">
          <span class="grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-ink-700 text-white shrink-0">
            <x-store.ui name="telegram" class="w-4 h-4" /></span>
          <span class="text-sm font-semibold">{{ __('store.telegram_support') }}</span>
          <x-store.ui name="arrow-right" class="w-4 h-4 ml-auto text-ink-400 icon-nudge" /></a>
      @endif
      @if (filled($contact->phone))
        <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}"
          class="group flex items-center gap-3 p-3 rounded-[var(--radius-control)] hover:bg-ink-50 transition">
          <span class="grid place-items-center w-9 h-9 rounded-[var(--radius-control)] bg-brand-600 text-white shrink-0">
            <x-store.ui name="phone" class="w-4 h-4" /></span>
          <span class="tnum text-sm font-semibold">{{ __('store.call', ['number' => $contact->phone]) }}</span>
          <x-store.ui name="arrow-right" class="w-4 h-4 ml-auto text-ink-400 icon-nudge" /></a>
      @endif
    </div>
  </div>

  {{-- The one round thing on the site, because a call button is a call button. It is still a
       .btn underneath, so it is stamped on the same 2px offset as every other action. --}}
  <button @click="open=!open" class="btn btn-primary btn-lg sheen rounded-full pl-3 pr-5">
    <span class="relative grid place-items-center w-7 h-7 rounded-full bg-white/20">
      <x-store.ui name="headset" class="w-4 h-4" />
      <span class="absolute -top-0.5 -right-0.5 live-dot ring-2 ring-brand-600"></span>
    </span>
    <span x-text="open ? @js(__('store.close')) : @js(__('store.support'))">{{ __('store.support') }}</span>
  </button>
</div>
