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
      <x-store.ui name="currency" class="w-3.5 h-3.5" />
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
    <div class="pt-3">
      <p class="px-4 pb-2 text-xs font-medium text-ink-400">{{ __('store.currency') }}</p>
      <div class="flex flex-wrap items-center gap-2 px-4">
        @foreach ($currencies as $currency)
          <a href="{{ route('currency.switch', $currency) }}"
            @if ($currency === $currentCurrency) aria-current="true" @endif
            class="chip h-9 px-3.5 {{ $currency === $currentCurrency ? 'chip-on' : '' }}">{{ \App\Support\Currencies::symbol($currency) }}
            {{ $currency }}</a>
        @endforeach
      </div>
    </div>
  @endif
@endif
