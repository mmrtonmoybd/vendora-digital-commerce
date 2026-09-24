@props(['title', 'lead' => null, 'crumbs' => [], 'padding' => 'pb-16'])

{{-- The inner-page masthead: trail, heading, optional lead, on the same navy ground the
     home page opens with. Left-aligned, because a breadcrumb read from the centre is a
     breadcrumb nobody follows. --}}
<div class="grid-field {{ $padding }}">
  <div class="pt-8 md:pt-12 rise-in">
    <nav class="flex items-center gap-2 text-sm text-white/55" aria-label="{{ __('store.breadcrumb') }}">
      <a href="{{ route('home') }}" class="hover:text-white transition">{{ __('store.home') }}</a>
      @foreach ($crumbs as $label => $url)
        <x-store.ui name="chevron-right" class="w-3.5 h-3.5 opacity-50" />
        @if ($url)
          <a href="{{ $url }}" class="hover:text-white transition">{{ $label }}</a>
        @else
          <span class="text-white">{{ $label }}</span>
        @endif
      @endforeach
    </nav>

    <h1 class="mt-4 h-page text-white">{{ $title }}</h1>

    @if ($lead)
      <p class="mt-3 text-[16.5px] text-white/65 leading-relaxed max-w-[62ch]">{{ $lead }}</p>
    @endif

    {{ $slot }}
  </div>
</div>
