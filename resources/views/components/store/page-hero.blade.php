@props(['title', 'lead' => null, 'crumbs' => [], 'padding' => 'pb-16'])

{{--
  The inner-page masthead: trail, heading, optional lead.

  It renders inside the header's slot, so the ink ground and its guilloché are already
  underneath it — this block draws no ground of its own, or the engine-turning would be
  printed twice over itself. Left-aligned, because a breadcrumb read from the centre is a
  breadcrumb nobody follows.

  The slot below the lead is load-bearing: the search page puts its field there, so this is
  the masthead and the page's main control at once.
--}}
<div class="{{ $padding }}">
  <div class="pt-8 md:pt-12 rise-in">
    <nav class="flex flex-wrap items-center gap-2 text-sm text-white/55" aria-label="{{ __('store.breadcrumb') }}">
      <a href="{{ route('home') }}" class="underline-grow hover:text-white transition">{{ __('store.home') }}</a>
      @foreach ($crumbs as $label => $url)
        <x-store.ui name="chevron-right" class="w-3.5 h-3.5 opacity-50" />
        @if ($url)
          <a href="{{ $url }}" class="underline-grow hover:text-white transition">{{ $label }}</a>
        @else
          <span class="text-white">{{ $label }}</span>
        @endif
      @endforeach
    </nav>

    <h1 class="mt-4 h-page text-white">{{ $title }}</h1>

    @if ($lead)
      <p class="lede mt-3 text-[16.5px] text-white/65">{{ $lead }}</p>
    @endif

    {{ $slot }}
  </div>
</div>
