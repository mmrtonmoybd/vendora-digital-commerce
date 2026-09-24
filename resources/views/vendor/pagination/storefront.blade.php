@if ($paginator->hasPages())
  <nav class="mt-10 flex justify-center gap-1.5" aria-label="{{ __('store.pagination') }}">
    @if (!$paginator->onFirstPage())
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="tap grid place-items-center w-10 h-10 rounded-[var(--radius-control)] bg-white border border-navy-200 text-navy-600 hover:border-navy-400 hover:text-navy-900 transition" aria-label="{{ __('store.previous') }}"><x-store.ui name="arrow-left" class="w-4 h-4" /></a>
    @endif
    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="grid place-items-center w-10 h-10 rounded-[var(--radius-control)] text-sm font-semibold text-navy-400">{{ $element }}</span>
      @endif
      @if (is_array($element))
        @foreach ($element as $page => $url)
          <a href="{{ $url }}" @if ($page == $paginator->currentPage()) aria-current="page" @endif class="tnum tap grid place-items-center w-10 h-10 rounded-[var(--radius-control)] text-sm font-semibold transition {{ $page == $paginator->currentPage() ? 'bg-navy-900 text-white' : 'bg-white border border-navy-200 text-navy-600 hover:border-navy-400 hover:text-navy-900' }}">{{ $page }}</a>
        @endforeach
      @endif
    @endforeach
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="tap grid place-items-center w-10 h-10 rounded-[var(--radius-control)] bg-white border border-navy-200 text-navy-600 hover:border-navy-400 hover:text-navy-900 transition" aria-label="{{ __('store.next') }}"><x-store.ui name="arrow-right" class="w-4 h-4" /></a>
    @endif
  </nav>
@endif
