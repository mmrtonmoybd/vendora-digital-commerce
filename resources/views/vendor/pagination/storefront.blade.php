{{--
  The paginator, for the lists that are paged rather than extended in place.

  Page controls are stamped like every other control on the site: the page being read is
  pressed in ink, the rest sit quiet until the cursor finds them.
--}}
@if ($paginator->hasPages())
  <nav class="rule-dashed mt-10 pt-8 flex flex-wrap justify-center gap-1.5" aria-label="{{ __('store.pagination') }}">
    @if (!$paginator->onFirstPage())
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-quiet w-11 px-0" aria-label="{{ __('store.previous') }}">
        <x-store.ui name="arrow-left" class="w-4 h-4" /></a>
    @endif
    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="grid place-items-center w-11 h-11 font-display text-sm font-bold text-ink-400">{{ $element }}</span>
      @endif
      @if (is_array($element))
        @foreach ($element as $page => $url)
          <a href="{{ $url }}" @if ($page == $paginator->currentPage()) aria-current="page" @endif
            class="tnum btn w-11 px-0 {{ $page == $paginator->currentPage() ? 'btn-dark' : 'btn-quiet' }}">{{ $page }}</a>
        @endforeach
      @endif
    @endforeach
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="group btn btn-quiet w-11 px-0" aria-label="{{ __('store.next') }}">
        <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
    @endif
  </nav>
@endif
