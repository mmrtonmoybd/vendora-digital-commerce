@props(['variant' => 'strip'])
{{--
    The language switch.

    Built out of classes the template already uses rather than a new visual idiom: on the
    top strip it reads as one more item in the row beside the phone number and the support
    hours, and in the mobile drawer it matches the links above it.

    Plain links, not a form. Public pages are served from the edge cache with no CSRF token
    in the HTML, so a posted switcher would be dead until the hydration script had run.

    It renders nothing at all when there is only one language to choose from.
--}}
@php
  $locales = \App\Support\Locales::enabled();
  $current = app()->getLocale();
@endphp

@if (\App\Support\Locales::showsSwitcher())
  @if ($variant === 'strip')
    <span class="flex items-center gap-1.5" role="group" aria-label="{{ __('store.language') }}">
      <x-store.ui name="globe" class="w-3.5 h-3.5" />
      @foreach ($locales as $index => $locale)
        @if ($index > 0)
          <span class="text-white/30" aria-hidden="true">/</span>
        @endif
        <a href="{{ route('locale.switch', $locale) }}"
          hreflang="{{ $locale }}"
          @if ($locale === $current) aria-current="true" @endif
          class="{{ $locale === $current ? 'text-white font-semibold' : 'hover:text-white' }}">{{ \App\Support\Locales::nativeName($locale) }}</a>
      @endforeach
    </span>
  @else
    <div class="pt-3">
      <p class="px-4 pb-2 text-xs font-medium text-ink-400">{{ __('store.language') }}</p>
      <div class="flex flex-wrap items-center gap-2 px-4">
        @foreach ($locales as $locale)
          <a href="{{ route('locale.switch', $locale) }}"
            hreflang="{{ $locale }}"
            @if ($locale === $current) aria-current="true" @endif
            class="chip h-9 px-3.5 {{ $locale === $current ? 'chip-on' : '' }}">{{ \App\Support\Locales::nativeName($locale) }}</a>
        @endforeach
      </div>
    </div>
  @endif
@endif
