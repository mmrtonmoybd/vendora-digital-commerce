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
    main-class="-mt-6 relative z-10"
>
<x-slot:hero>
    <x-store.page-hero
        :title="$category->t('name')"
        :lead="$category->t('short_description')"
        :crumbs="['Categories' => route('search'), $category->name => null]"
    />
</x-slot:hero>

  <div class="max-w-7xl mx-auto px-4">
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-2">@foreach ($siblings as $chip)<a href="{{ route('category.show', $chip) }}" class="shrink-0 inline-flex items-center gap-2 h-10 px-4 rounded-full text-sm font-semibold {{ $chip->is($category) ? 'bg-brand-600 text-white' : 'bg-white ring-1 ring-stone-200 hover:ring-brand-300' }}"><x-store.icon :name="$chip->icon_key" class="w-4 h-4" /> {{ $chip->name }}</a>@endforeach</div>
    <form method="GET" class="mt-6 flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
      <p class="text-stone-600"><b class="text-ink">{{ $products->total() }}</b> products</p>
      <div class="flex gap-2">
        <label class="inline-flex items-center gap-2 h-10 px-3 rounded-xl bg-white ring-1 ring-stone-200 text-sm"><input type="checkbox" name="in_stock" value="1" @checked($inStockOnly) onchange="this.form.submit()" class="rounded text-brand-600 focus:ring-brand-500"> {{ __('shop.in_stock_only') }}</label>
        <select name="sort" onchange="this.form.submit()" class="h-10 rounded-xl border-stone-200 text-sm focus:border-brand-500 focus:ring-brand-500">@foreach (config('storefront.sorts') as $value => $label)<option value="{{ $value }}" @selected($sort === $value)>Sort: {{ $label }}</option>@endforeach</select>
      </div>
    </form>
    <div class="mt-6"><div class="grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
    @forelse ($products as $product)
<x-store.product-card :product="$product" />
@empty
    <div class="col-span-full py-16 text-center px-6">
      <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-brand-50 text-brand-600"><x-store.icon :name="$category->icon_key" class="w-7 h-7" /></span>
      <h3 class="mt-4 font-display text-xl font-bold">{{ __('shop.nothing_here_yet') }}</h3><p class="text-stone-600">{{ __('shop.restocking_category') }}</p>
      <a href="{{ route('home') }}#categories" class="mt-5 inline-flex h-11 px-5 items-center rounded-full bg-brand-600 text-white font-semibold">{{ __('store.browse_categories') }}</a>
    </div>
@endforelse</div></div>
    {{ $products->links('vendor.pagination.storefront') }}
  </div>
<div class="h-20"></div>
</x-layouts.storefront>
