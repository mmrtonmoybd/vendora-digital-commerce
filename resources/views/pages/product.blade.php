@php
    $discount = $product->discountPercent();
    $star = '<svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    $tick = '<span class="grid place-items-center w-5 h-5 rounded-full bg-brand-100 text-brand-700"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span>';
    $gallery = collect($product->gallery ?? [])->take(4);

    $deliveryStep = sprintf(
        'Our team grants %s access to that email%s, usually within %d minutes.',
        $product->access_role ?: 'account',
        $product->platform ? ' on '.$product->platform->name : '',
        $product->fulfillment_sla_minutes,
    );

    $schema = array_filter([
        '@type' => 'Product',
        'name' => $product->t('name'),
        'description' => $product->t('short'),
        'sku' => $product->slug,
        'category' => $product->category?->name,
        'image' => $product->image ? Storage::disk('public')->url($product->image) : null,
        'brand' => ['@type' => 'Brand', 'name' => $store->general()->site_name],
        'aggregateRating' => $product->reviews_count > 0 ? [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $product->ratingValue(),
            'reviewCount' => (string) $product->reviews_count,
        ] : null,
        'offers' => [
            '@type' => 'Offer',
            'url' => route('product.show', $product),
            'price' => \App\Support\Money::toDecimal($product->price),
            'priceCurrency' => \App\Support\Money::currencyCode(),
            'availability' => $product->isInStock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        ],
    ]);
@endphp

<x-layouts.storefront
    :seo-title="$product->t('seo_title') ?: $product->t('name')"
    :seo-description="$product->t('seo_description') ?: $product->t('short')"
    :seo-image="$product->image ? Storage::disk('public')->url($product->image) : null"
    seo-type="product"
    :seo-schema="$schema"
    main-class="-mt-4 relative z-10"
>
<x-slot:hero>
    <x-store.page-hero
        :title="$product->t('name')"
        :crumbs="[$product->category?->name => route('category.show', $product->category), 'Product' => null]"
    />
</x-slot:hero>

<div data-track="view_content" data-track-payload='@json(app(\App\Services\TrackingService::class)->product($product))'></div>

  <div class="max-w-7xl mx-auto px-4">
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-4 md:p-8 grid lg:grid-cols-2 gap-8">
      <div class="group relative">@if (filled($product->t('badge_text')))<div class="ribbon {{ $product->badgeClass() }}"><span>{{ $product->t('badge_text') }}</span></div>@endif<div class="aspect-square md:aspect-[5/4] rounded-xl bg-gradient-to-br {{ $product->gradient }} art-rings relative grid place-items-center overflow-hidden">
        @if ($product->image)<img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->t('name') }}" class="absolute inset-0 w-full h-full object-cover">@endif
        <span class="grid place-items-center w-24 h-24 rounded-3xl bg-white/15 ring-1 ring-white/25 backdrop-blur-sm text-white rotate-[-6deg] group-hover:rotate-0 group-hover:scale-105 transition duration-300"><x-store.icon :name="$product->icon_key" stroke-width="1.6" class="w-20 h-20" /></span>
        <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-black/25 text-white text-[10px] font-semibold tracking-wide uppercase">{{ $product->category?->t('name') }}</span>
      </div>
        @if ($gallery->isNotEmpty())<div class="mt-3 grid grid-cols-4 gap-2">@foreach ($gallery as $index => $image)<button class="aspect-square rounded-xl bg-gradient-to-br {{ $product->gradient }} {{ $index === 0 ? 'ring-2 ring-offset-2 ring-brand-600' : 'opacity-60 hover:opacity-100' }}" aria-label="Image {{ $index + 1 }}"></button>@endforeach</div>@endif</div>
      <div>
        <a href="{{ route('category.show', $product->category) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700"><x-store.icon :name="$product->category?->icon_key ?? 'play'" class="w-4 h-4" /> {{ $product->category?->t('name') }}</a>
        <h2 class="mt-2 font-display text-2xl md:text-3xl font-bold leading-tight">{{ $product->t('name') }}</h2>
        <div class="mt-2 flex items-center gap-3 text-sm"><span class="flex">@for ($i = 0; $i < $product->ratingStars(); $i++){!! $star !!}@endfor</span><span class="text-stone-600">{{ $product->ratingValue() }} · {{ number_format($product->reviews_count) }} {{ __('shop.reviews') }}</span>@if ($product->isInStock())<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 text-xs font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ __('shop.pcs', ['count' => $product->stock]) }}</span>@else<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 ring-1 ring-red-600/20 text-xs font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ __('shop.pcs', ['count' => 0]) }}</span>@endif</div>
        <div class="mt-5 flex items-end gap-3"><span class="font-display text-4xl font-bold text-brand-700">{{ \App\Support\Money::format($product->price) }}</span>@if ($discount)<s class="text-lg text-stone-400">{{ \App\Support\Money::format((int) $product->regular_price) }}</s><span class="mb-1 px-2 py-0.5 rounded-md bg-emerald-600 text-white text-xs font-bold">{{ __('shop.save_percent', ['percent' => $discount]) }}</span>@endif</div>
        <p class="mt-4 text-stone-600 leading-relaxed">{{ $product->t('short') }}</p>
        @if ($product->featureList())<ul class="mt-5 grid sm:grid-cols-2 gap-2">@foreach ($product->featureList() as $feature)<li class="flex items-center gap-2 text-sm">{!! $tick !!}{{ $feature }}</li>@endforeach</ul>@endif
        <div class="mt-7 grid sm:grid-cols-2 gap-3">
          @if ($product->isInStock())
          <button type="button" @click="window.Livewire.dispatch('open-buy', { slug: '{{ $product->slug }}' })" class="h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/30 hover:from-brand-700 hover:to-brand-900">{{ __('shop.buy_now') }} <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg></button>
          <button type="button" @click="window.Livewire.dispatch('quick-add', { slug: '{{ $product->slug }}' })" class="h-12 inline-flex items-center justify-center gap-2 rounded-xl ring-1 ring-brand-600 text-brand-700 font-semibold hover:bg-brand-50"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg> {{ __('shop.add_to_cart') }}</button>
          @else
          <button type="button" disabled class="h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-stone-100 text-stone-400 font-semibold cursor-not-allowed sm:col-span-2">{{ __('shop.out_of_stock') }}</button>
          @endif
        </div>
        <div class="mt-6 grid grid-cols-3 gap-2 text-center text-xs">
          <div class="p-3 rounded-xl bg-surface ring-1 ring-stone-200"><span class="mx-auto grid place-items-center w-8 h-8 rounded-lg bg-brand-600 text-white mb-1.5"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg></span><b class="block text-ink">{{ __('shop.step_fast') }}</b>~{{ $product->fulfillment_sla_minutes }} min access</div><div class="p-3 rounded-xl bg-surface ring-1 ring-stone-200"><span class="mx-auto grid place-items-center w-8 h-8 rounded-lg bg-brand-600 text-white mb-1.5"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg></span><b class="block text-ink">{{ __('shop.warranty') }}</b>{{ $product->warranty_days ? $product->warranty_days.'-day cover' : 'Support cover' }}</div><div class="p-3 rounded-xl bg-surface ring-1 ring-stone-200"><span class="mx-auto grid place-items-center w-8 h-8 rounded-lg bg-brand-600 text-white mb-1.5"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/></svg></span><b class="block text-ink">{{ __('store.support') }}</b>{{ $store->general()->support_hours }}</div>
        </div>
      </div>
    </div>

    <!-- tabs -->
    <div class="mt-8 bg-white rounded-3xl shadow-card ring-1 ring-black/5" x-data="{t:'desc'}">
      <div class="flex gap-1 p-2 border-b border-stone-100 overflow-x-auto no-scrollbar">
        <button @click="t='desc'" class="shrink-0 h-10 px-4 rounded-xl text-sm font-semibold" :class="t==='desc' ? 'bg-brand-600 text-white' : 'hover:bg-brand-50'">{{ __('shop.description') }}</button><button @click="t='delivery'" class="shrink-0 h-10 px-4 rounded-xl text-sm font-semibold" :class="t==='delivery' ? 'bg-brand-600 text-white' : 'hover:bg-brand-50'">{{ __('shop.delivery_instructions') }}</button>@if ($product->faqs->isNotEmpty())<button @click="t='faq'" class="shrink-0 h-10 px-4 rounded-xl text-sm font-semibold" :class="t==='faq' ? 'bg-brand-600 text-white' : 'hover:bg-brand-50'">{{ __('shop.faqs') }}</button>@endif
      </div>
      <div class="p-6 md:p-8">
        <div x-show="t==='desc'" class="prose prose-stone max-w-none prose-headings:font-display prose-a:text-brand-700">
          {!! clean($product->t('description')) !!}
        </div>
        <div x-show="t==='delivery'" x-cloak class="grid md:grid-cols-3 gap-4">
          <div class="p-5 rounded-2xl bg-surface ring-1 ring-stone-200"><span class="grid place-items-center w-9 h-9 rounded-full bg-brand-600 text-white font-bold font-display">1</span><h4 class="mt-3 font-display font-semibold">{{ __('shop.step_pay') }}</h4><p class="mt-1 text-sm text-stone-600">{{ __('shop.choose_payment_hint') }}</p></div><div class="p-5 rounded-2xl bg-surface ring-1 ring-stone-200"><span class="grid place-items-center w-9 h-9 rounded-full bg-brand-600 text-white font-bold font-display">2</span><h4 class="mt-3 font-display font-semibold">{{ __('shop.step_invite') }}</h4><p class="mt-1 text-sm text-stone-600">{{ $deliveryStep }}</p></div><div class="p-5 rounded-2xl bg-surface ring-1 ring-stone-200"><span class="grid place-items-center w-9 h-9 rounded-full bg-brand-600 text-white font-bold font-display">3</span><h4 class="mt-3 font-display font-semibold">{{ __('shop.step_accept') }}</h4><p class="mt-1 text-sm text-stone-600">{{ $product->platform?->invite_instructions ?: 'Open your inbox, accept the invitation and you are in.' }}</p></div>
        </div>
        @if ($product->faqs->isNotEmpty())
        <div x-show="t==='faq'" x-cloak class="divide-y divide-stone-100">
          @foreach ($product->faqs as $faq)<details class="group py-4"><summary class="flex justify-between items-center cursor-pointer font-semibold list-none">{{ $faq->t('question') }}<span class="text-brand-600 group-open:rotate-180 transition"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span></summary><p class="mt-2 text-stone-600">{{ $faq->t('answer') }}</p></details>@endforeach
        </div>
        @endif
      </div>
    </div>

    @if ($related->isNotEmpty())
    <!-- related -->
    <section class="mt-14">
      <div class="flex items-end justify-between mb-5"><h2 class="font-display text-2xl font-bold">{{ __('shop.you_may_also_like') }}</h2><a href="{{ route('category.show', $product->category) }}" class="text-sm font-semibold text-brand-700 hover:underline">{{ __('store.view_all') }}</a></div>
      <div class="grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
    @foreach ($related as $item)
<x-store.product-card :product="$item" />
@endforeach</div>
    </section>
    @endif
  </div>
<div class="h-20"></div>
</x-layouts.storefront>
