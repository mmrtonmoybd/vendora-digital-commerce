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
          <x-store.ui name="search" class="field-affix w-5 h-5 text-navy-400" />
          <input name="q" value="{{ $term }}" type="search" autofocus placeholder="{{ __('store.search_placeholder') }}"
            class="field field-icon h-14 pl-12 pr-32 text-base shadow-xl shadow-navy-950/20">
          <button class="btn btn-primary absolute right-2 top-2 sheen">{{ __('store.search') }}</button>
        </label>
      </form>
    </x-store.page-hero>
  </x-slot:hero>

  @if ($term !== '')
    <div data-track="search" data-track-payload='@json(app(\App\Services\TrackingService::class)->search($term))'></div>
  @endif

  <div class="wrap">
    @if ($term !== '')
      <p class="text-navy-500"><b class="tnum text-navy-900">{{ $products->total() }}</b>
        {!! __('shop.search_results_for', ['term' => '<span class="font-semibold text-brand-700">' . e($term) . '</span>']) !!}</p>
    @endif

    <div data-product-grid class="stagger mt-6 grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
      @foreach ($products as $product)
        <x-store.product-card :product="$product" />
      @endforeach
    </div>

    @if ($products->isEmpty())
      <div class="panel py-16 px-6 text-center">
        <span class="inline-grid place-items-center w-16 h-16 rounded-[var(--radius-panel)] bg-navy-50 text-navy-400 float">
          <x-store.ui name="search" class="w-7 h-7" /></span>
        <h2 class="mt-4 font-display text-xl font-semibold text-navy-900">{{ __('shop.no_products_found') }}</h2>
        <p class="mt-1 text-navy-500">{{ __('shop.try_another_keyword') }}</p>
        <a href="{{ route('home') }}#categories" class="btn btn-primary mt-6">{{ __('store.browse_categories') }}
          <x-store.ui name="arrow-right" class="w-4 h-4" /></a>
      </div>
    @endif

    <x-store.load-more :paginator="$products" />
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
