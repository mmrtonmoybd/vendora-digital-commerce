@props(['title', 'lead' => null, 'crumbs' => [], 'padding' => 'pb-16'])

{{-- The inner-page hero: breadcrumb, heading and an optional lead, inside the red header
     ground. Every page but the home page uses it. --}}
<div class="{{ $padding }}">
  <div class="pt-6 md:pt-10 text-center">
    <nav class="text-sm text-white/70" aria-label="{{ __('store.breadcrumb') }}"><a href="{{ route('home') }}" class="hover:text-white">{{ __('store.home') }}</a>
      @foreach ($crumbs as $label => $url)
        <span class="opacity-50">/</span>
        @if ($url)
        <a href="{{ $url }}" class="hover:text-white">{{ $label }}</a>@else<span class="text-white">{{ $label }}</span>
        @endif
      @endforeach
    </nav>
    <h1 class="mt-3 font-display text-3xl md:text-5xl font-bold">{{ $title }}</h1>
    <div class="flex justify-center">
      @if ($lead)
        <p class="mt-3 text-white/80 max-w-2xl">{{ $lead }}</p>
      @endif
    </div>
  </div>
</div>
