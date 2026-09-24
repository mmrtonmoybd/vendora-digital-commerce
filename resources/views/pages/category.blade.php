@php
  $schema = [
      '@type' => 'CollectionPage',
      'name' => $category->t('name'),
      'description' => $category->t('short_description'),
      'url' => route('category.show', $category),
  ];
@endphp

<x-layouts.storefront
  :seo-title="$category->t('seo_title') ?: $category->t('name')"
  :seo-description="$category->t('seo_description') ?: $category->t('short_description')"
  :seo-schema="$schema"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero
      :title="$category->t('name')"
      :lead="$category->t('short_description')"
      :crumbs="[__('store.categories') => route('search'), $category->name => null]"
      padding="pb-24" />
  </x-slot:hero>

  <div class="wrap">
    {{-- Sibling categories, so a buyer who landed on the wrong one can step sideways
         without going back to the home page. --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
      @foreach ($siblings as $chip)
        <a href="{{ route('category.show', $chip) }}" class="chip {{ $chip->is($category) ? 'chip-on' : '' }}"
          @if ($chip->is($category)) aria-current="page" @endif>
          <x-store.icon :name="$chip->icon_key" class="w-4 h-4" />{{ $chip->name }}</a>
      @endforeach
    </div>

    <form method="GET" class="panel mt-5 p-3 flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
      <p class="flex items-center gap-2 px-1 text-sm text-navy-500"><x-store.ui name="filter" class="w-4 h-4 text-navy-400" />
        <b class="tnum text-navy-900">{{ $products->total() }}</b>{{ __('shop.products_found') }}
      </p>
      <div class="flex flex-wrap gap-2">
        <label class="chip cursor-pointer {{ $inStockOnly ? 'chip-on' : '' }}">
          <input type="checkbox" name="in_stock" value="1" @checked($inStockOnly) onchange="this.form.submit()" class="check">
          {{ __('shop.in_stock_only') }}</label>
        <select name="sort" onchange="this.form.submit()" class="field w-auto text-sm">
          @foreach (config('storefront.sorts') as $value => $label)
            <option value="{{ $value }}" @selected($sort === $value)>{{ __('shop.sort_by') }}: {{ $label }}</option>
          @endforeach
        </select>
      </div>
    </form>

    <div data-product-grid class="stagger mt-6 grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
      @forelse ($products as $product)
        <x-store.product-card :product="$product" />
      @empty
        <div class="col-span-full panel py-16 px-6 text-center">
          <span class="inline-grid place-items-center w-16 h-16 rounded-[var(--radius-panel)] bg-navy-50 text-navy-400 float">
            <x-store.icon :name="$category->icon_key" class="w-7 h-7" /></span>
          <h2 class="mt-4 font-display text-xl font-semibold text-navy-900">{{ __('shop.nothing_here_yet') }}</h2>
          <p class="mt-1 text-navy-500">{{ __('shop.restocking_category') }}</p>
          <a href="{{ route('home') }}#categories" class="btn btn-primary mt-6">{{ __('store.browse_categories') }}
            <x-store.ui name="arrow-right" class="w-4 h-4" /></a>
        </div>
      @endforelse
    </div>

    <x-store.load-more :paginator="$products" />
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
