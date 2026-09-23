<x-layouts.storefront
    :seo-title="$term !== '' ? __('shop.search_heading', ['term' => $term]) : __('store.search_products')"
    :seo-description="__('shop.search_seo_description')"
    seo-robots="noindex,follow"
>
<x-slot:hero>
    <div class="pb-16"><div class="pt-6 md:pt-10 max-w-2xl mx-auto text-center">
      <h1 class="font-display text-3xl md:text-5xl font-bold">{{ __('store.search_products') }}</h1>
      <form action="{{ route('search') }}" method="GET" class="mt-6 relative">
        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></span>
        <input name="q" value="{{ $term }}" type="search" autofocus placeholder="{{ __('store.search_placeholder') }}" class="w-full h-14 pl-14 pr-32 rounded-full border-0 text-ink shadow-xl focus:ring-4 focus:ring-white/40">
        <button class="absolute right-2 top-2 h-10 px-6 rounded-full bg-brand-600 hover:bg-brand-700 font-semibold">{{ __('store.search') }}</button>
      </form></div></div>
</x-slot:hero>

@if ($term !== '')
<div data-track="search" data-track-payload='@json(app(\App\Services\TrackingService::class)->search($term))'></div>
@endif

  <div class="max-w-7xl mx-auto px-4 pt-4">
    @if ($term !== '')<p class="text-stone-600"><b class="text-ink">{{ $products->total() }}</b> {!! __('shop.search_results_for', ['term' => '<span class="text-brand-700 font-semibold">'.e($term).'</span>']) !!}</p>@endif
    <div class="mt-6 grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
      @foreach ($products as $product)
<x-store.product-card :product="$product" />
@endforeach
    </div>
    @if ($products->isEmpty())
    <div class="py-20 text-center">
      <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-brand-50 text-brand-600"><svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></span>
      <h2 class="mt-4 font-display text-xl font-bold">{{ __('shop.no_products_found') }}</h2><p class="text-stone-600">{{ __('shop.try_another_keyword') }}</p>
      <a href="{{ route('home') }}#categories" class="mt-5 inline-flex h-11 px-5 items-center rounded-full bg-brand-600 text-white font-semibold">{{ __('store.browse_categories') }}</a>
    </div>
    @endif
    {{ $products->links('vendor.pagination.storefront') }}
  </div>
<div class="h-20"></div>
</x-layouts.storefront>
