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
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true">
        <circle cx="12" cy="12" r="10" />
        <path d="M2 12h20" />
        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
      </svg>
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
    <div class="pt-2">
      <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ __('store.language') }}</p>
      <div class="flex items-center gap-2 px-3">
        @foreach ($locales as $locale)
          <a href="{{ route('locale.switch', $locale) }}"
            hreflang="{{ $locale }}"
            @if ($locale === $current) aria-current="true" @endif
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold {{ $locale === $current ? 'bg-brand-600 text-white' : 'bg-stone-100 text-ink hover:bg-brand-50' }}">{{ \App\Support\Locales::nativeName($locale) }}</a>
        @endforeach
      </div>
    </div>
  @endif
@endif
