@props(['paginator'])

{{--
  The control under a catalogue list.

  It is a link to the next page, not a button pretending to be one: resources/js/load-more.js
  intercepts the click and appends the next page's cards in place, and with JavaScript off
  the same element simply takes the visitor to page two.

  The perforation above it closes the list the way the tear on a pass closes the artwork —
  what is below the line is the count, not more catalogue.
--}}
@if ($paginator->hasMorePages())
  <div class="rule-dashed mt-10 pt-8 flex flex-col items-center gap-3">
    <a href="{{ $paginator->nextPageUrl() }}" rel="next" data-load-more class="btn btn-quiet btn-lg group">
      <span data-load-more-rest class="inline-flex items-center gap-2">{{ __('store.load_more') }}
        <x-store.ui name="chevron-down" class="w-4 h-4 icon-rise" /></span>
      <span data-load-more-busy hidden class="inline-flex items-center gap-2">{{ __('store.loading') }}
        <x-store.ui name="refresh" class="w-4 h-4 spin-slow" /></span>
    </a>
    <p class="tnum text-xs text-ink-500">
      {{ __('store.showing_of', ['shown' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</p>
  </div>
@elseif ($paginator->total() > $paginator->perPage())
  <p class="tnum rule-dashed mt-10 pt-6 text-center text-xs text-ink-500">
    {{ __('store.showing_of', ['shown' => $paginator->total(), 'total' => $paginator->total()]) }}</p>
@endif
