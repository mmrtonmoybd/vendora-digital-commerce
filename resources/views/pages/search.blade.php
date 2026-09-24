<x-layouts.storefront
  :seo-title="$term !== '' ? __('shop.search_heading', ['term' => $term]) : __('store.search_products')"
  :seo-description="__('shop.search_seo_description')"
  seo-robots="noindex,follow"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="__('store.search_products')" :crumbs="[__('store.search') => null]" padding="pb-24">
      {{-- The field is the page. It carries the term back so a refined search starts from
           what was already typed. --}}
      <form action="{{ route('search') }}" method="GET" class="mt-7 max-w-2xl">
        <label class="relative block">
          <x-store.ui name="search" class="field-affix w-5 h-5 text-ink-400" />
          <input name="q" value="{{ $term }}" type="search" autofocus placeholder="{{ __('store.search_placeholder') }}"
            class="field field-icon h-14 pl-12 pr-32 text-base">
          <button class="btn btn-primary sheen absolute right-2 top-1.5">{{ __('store.search') }}</button>
        </label>
      </form>
    </x-store.page-hero>
  </x-slot:hero>

  @if ($term !== '')
    <div data-track="search" data-track-payload='@json(app(\App\Services\TrackingService::class)->search($term))'></div>
  @endif

  <div class="wrap">
    @if ($term !== '')
      {{-- The same docket header the category grid carries, so a count means the same
           thing wherever the visitor meets one: the figure first, the term after it. --}}
      <div class="reveal panel px-4 py-3 flex flex-wrap items-baseline gap-x-2 gap-y-1">
        <x-store.ui name="search" class="w-4 h-4 self-center text-ink-400" />
        <span class="tnum font-display text-[22px] font-extrabold leading-none text-ink-900">{{ $products->total() }}</span>
        <span class="text-sm text-ink-500">
          {!! __('shop.search_results_for', ['term' => '<span class="font-semibold text-brand-700">' . e($term) . '</span>']) !!}</span>
      </div>
    @endif

    <div data-product-grid class="stagger mt-6 grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
      @foreach ($products as $product)
        <x-store.product-card :product="$product" />
      @endforeach
    </div>

    @if ($products->isEmpty())
      {{-- A search that found nothing is still a page with one job: get the visitor to a
           list that does have something on it. --}}
      <div class="panel py-16 px-6 text-center">
        <span class="well w-16 h-16 mx-auto rounded-[var(--radius-panel)] text-ink-400 float">
          <x-store.ui name="search" class="w-7 h-7" /></span>
        <h2 class="mt-4 font-display text-[20px] font-bold text-ink-900">{{ __('shop.no_products_found') }}</h2>
        <p class="lede mt-1.5 mx-auto">{{ __('shop.try_another_keyword') }}</p>
        <a href="{{ route('home') }}#categories" class="group btn btn-primary sheen mt-6">{{ __('store.browse_categories') }}
          <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
      </div>
    @endif

    <x-store.load-more :paginator="$products" />
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
