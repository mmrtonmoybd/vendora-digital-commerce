@props(['variant' => 'strip'])
{{--
    The display-currency switch, built from the same classes as the language switcher
    beside it so the top strip stays one visual row.

    Renders nothing unless a second currency has been enabled in the panel.
--}}
@php
  $currencies = \App\Support\Currencies::enabled();
  $currentCurrency = \App\Support\Currencies::current();
@endphp

@if (\App\Support\Currencies::showsSwitcher())
  @if ($variant === 'strip')
    <span class="flex items-center gap-1.5" role="group" aria-label="{{ __('store.currency') }}">
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true">
        <circle cx="12" cy="12" r="10" />
        <path d="M12 6v12" />
        <path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.5 2.5 0 0 0 0 5h1a2.5 2.5 0 0 1 0 5h-1A2.5 2.5 0 0 1 9 14.5" />
      </svg>
      @foreach ($currencies as $index => $currency)
        @if ($index > 0)
          <span class="text-white/30" aria-hidden="true">/</span>
        @endif
        <a href="{{ route('currency.switch', $currency) }}"
          @if ($currency === $currentCurrency) aria-current="true" @endif
          class="{{ $currency === $currentCurrency ? 'text-white font-semibold' : 'hover:text-white' }}">{{ \App\Support\Currencies::symbol($currency) }}
          {{ $currency }}</a>
      @endforeach
    </span>
  @else
    <div class="pt-2">
      <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ __('store.currency') }}</p>
      <div class="flex items-center gap-2 px-3">
        @foreach ($currencies as $currency)
          <a href="{{ route('currency.switch', $currency) }}"
            @if ($currency === $currentCurrency) aria-current="true" @endif
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold {{ $currency === $currentCurrency ? 'bg-brand-600 text-white' : 'bg-stone-100 text-ink hover:bg-brand-50' }}">{{ \App\Support\Currencies::symbol($currency) }}
            {{ $currency }}</a>
        @endforeach
      </div>
    </div>
  @endif
@endif
