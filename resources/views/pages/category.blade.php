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

    {{--
      The docket header over the grid.

      The count is the answer the page is giving, so it is set as a figure — large, in
      tabular numerals — rather than as a sentence, and the two controls that change that
      figure sit opposite it. The select is held at the chip's height so the right-hand
      side reads as one ruled row rather than two controls of different sizes.
    --}}
    <form method="GET" class="reveal panel mt-5 px-4 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="flex items-baseline gap-2">
        <x-store.ui name="filter" class="w-4 h-4 self-center text-ink-400" />
        <span class="tnum font-display text-[22px] font-extrabold leading-none text-ink-900">{{ $products->total() }}</span>
        <span class="text-sm text-ink-500">{{ __('shop.products_found') }}</span>
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <label class="chip cursor-pointer {{ $inStockOnly ? 'chip-on' : '' }}">
          <input type="checkbox" name="in_stock" value="1" @checked($inStockOnly) onchange="this.form.submit()" class="check">
          {{ __('shop.in_stock_only') }}</label>
        <select name="sort" onchange="this.form.submit()" class="field h-10 w-auto text-sm">
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
        {{-- An empty category still has to point somewhere, so the panel carries the
             category's own mark and exactly one way out. --}}
        <div class="col-span-full panel py-16 px-6 text-center">
          <span class="well w-16 h-16 mx-auto rounded-[var(--radius-panel)] text-ink-400 float">
            <x-store.icon :name="$category->icon_key" class="w-7 h-7" /></span>
          <h2 class="mt-4 font-display text-[20px] font-bold text-ink-900">{{ __('shop.nothing_here_yet') }}</h2>
          <p class="lede mt-1.5 mx-auto">{{ __('shop.restocking_category') }}</p>
          <a href="{{ route('home') }}#categories" class="group btn btn-primary sheen mt-6">{{ __('store.browse_categories') }}
            <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
        </div>
      @endforelse
    </div>

    <x-store.load-more :paginator="$products" />
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
