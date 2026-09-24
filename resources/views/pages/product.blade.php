@php
  $discount = $product->discountPercent();
  $inStock = $product->isInStock();
  $gallery = collect($product->gallery ?? [])->take(4);
  $track = app(\App\Services\TrackingService::class)->product($product);

  $deliveryStep = sprintf(
      'Our team grants %s access to that email%s, usually within %d minutes.',
      $product->access_role ?: 'account',
      $product->platform ? ' on ' . $product->platform->name : '',
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
      'aggregateRating' =>
          $product->reviews_count > 0
              ? [
                  '@type' => 'AggregateRating',
                  'ratingValue' => (string) $product->ratingValue(),
                  'reviewCount' => (string) $product->reviews_count,
              ]
              : null,
      'offers' => [
          '@type' => 'Offer',
          'url' => route('product.show', $product),
          'price' => \App\Support\Money::toDecimal($product->price),
          'priceCurrency' => \App\Support\Money::currencyCode(),
          'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
      ],
  ]);
@endphp

<x-layouts.storefront
  :seo-title="$product->t('seo_title') ?: $product->t('name')"
  :seo-description="$product->t('seo_description') ?: $product->t('short')"
  :seo-image="$product->image ? Storage::disk('public')->url($product->image) : null"
  seo-type="product"
  :seo-schema="$schema"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero
      :title="$product->t('name')"
      :crumbs="[$product->category?->name => route('category.show', $product->category), __('shop.product') => null]"
      padding="pb-24" />
  </x-slot:hero>

  <div data-track="view_content" data-track-payload='@json($track)'></div>

  <div class="wrap">
    {{-- The record: artwork on the left, everything the buyer has to decide on the right. --}}
    <div class="panel p-4 md:p-8 grid lg:grid-cols-2 gap-8 lg:gap-12">
      <div class="group relative">
        @if (filled($product->t('badge_text')))
          <div class="ribbon {{ $product->badgeClass() }}"><span>{{ $product->t('badge_text') }}</span></div>
        @endif

        <div class="well aspect-square md:aspect-[5/4] rounded-[var(--radius-panel)] border border-navy-100">
          @if ($product->image)
            <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->t('name') }}"
              class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
          @else
            <span class="relative grid place-items-center w-28 h-28 rounded-[1.25rem] bg-navy-900 text-white float">
              <x-store.icon :name="$product->icon_key" stroke-width="1.5" class="w-14 h-14" /></span>
          @endif

          @if ($product->category)
            <a href="{{ route('category.show', $product->category) }}"
              class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 h-7 px-2.5 rounded-md bg-navy-950/75 text-white text-[11.5px] font-medium backdrop-blur-sm">
              <x-store.icon :name="$product->category->icon_key" class="w-3.5 h-3.5" />{{ $product->category->t('name') }}</a>
          @endif
        </div>

        @if ($gallery->isNotEmpty())
          <div class="mt-3 grid grid-cols-4 gap-2">
            @foreach ($gallery as $index => $image)
              <span
                class="well aspect-square rounded-[var(--radius-control)] border {{ $index === 0 ? 'border-brand-500' : 'border-navy-200' }}">
                <img src="{{ Storage::disk('public')->url($image) }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-fill">
              </span>
            @endforeach
          </div>
        @endif
      </div>

      <div>
        @if ($product->platform)
          <p class="flex items-center gap-2 text-sm text-navy-500"><x-store.ui name="layers"
              class="w-4 h-4 text-navy-400" />{{ $product->platform->t('name') }}</p>
        @endif

        <h2 class="mt-2 font-display text-[1.75rem] md:text-[2.125rem] font-bold leading-[1.12] tracking-[-0.02em] text-navy-900">
          {{ $product->t('name') }}</h2>

        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
          <span class="flex items-center gap-1 text-brand-600">
            @for ($i = 0; $i < $product->ratingStars(); $i++)
              <x-store.ui name="star" class="w-4 h-4 fill-current" stroke-width="1" />
            @endfor
          </span>
          <span class="tnum text-navy-500">{{ $product->ratingValue() }} · {{ number_format($product->reviews_count) }} {{ __('shop.reviews') }}</span>
          @if ($inStock)
            <span class="flag flag-live"><span class="live-dot"></span>{{ __('shop.seats_left', ['count' => $product->stock]) }}</span>
          @else
            <span class="flag flag-off">{{ __('shop.out_of_stock') }}</span>
          @endif
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-3">
          <span class="tnum font-display text-[2.5rem] font-bold leading-none text-navy-900">{{ \App\Support\Money::format($product->price) }}</span>
          @if ($discount)
            <s class="tnum text-lg text-navy-400">{{ \App\Support\Money::format((int) $product->regular_price) }}</s>
            <span class="tnum flag flag-live">{{ __('shop.save_percent', ['percent' => $discount]) }}</span>
          @endif
        </div>

        <p class="mt-4 text-navy-600 leading-relaxed max-w-[60ch]">{{ $product->t('short') }}</p>

        @if ($product->featureList())
          <ul class="mt-5 grid sm:grid-cols-2 gap-2.5">
            @foreach ($product->featureList() as $feature)
              <li class="flex items-start gap-2 text-sm text-navy-700">
                <span class="grid place-items-center w-5 h-5 rounded-full bg-seat-50 text-seat-700 shrink-0 mt-px"><x-store.ui name="check" class="w-3 h-3"
                    stroke-width="3" /></span>{{ $feature }}
              </li>
            @endforeach
          </ul>
        @endif

        <div class="mt-7 grid sm:grid-cols-2 gap-3">
          @if ($inStock)
            <button type="button" @click="window.Livewire.dispatch('buy-now', { slug: '{{ $product->slug }}' })" class="group btn btn-primary btn-lg sheen">
              <x-store.ui name="cart" class="w-4 h-4" />{{ __('shop.buy_now') }}<x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></button>
            <button type="button" @click="window.Livewire.dispatch('quick-add', { slug: '{{ $product->slug }}' })" data-track-click="add_to_cart"
              data-track-payload='@json($track)' class="btn btn-quiet btn-lg">
              <x-store.ui name="plus" class="w-4 h-4" />{{ __('shop.add_to_cart') }}</button>
          @else
            <button type="button" disabled class="btn btn-lg sm:col-span-2" aria-disabled="true">{{ __('shop.out_of_stock') }}</button>
          @endif
        </div>

        {{-- The three commitments, taken off the record rather than promised in copy. --}}
        <dl class="mt-6 grid grid-cols-3 gap-3">
          <div class="panel p-3.5">
            <dt class="flex items-center gap-1.5 text-[11.5px] text-navy-500"><x-store.ui name="zap"
                class="w-3.5 h-3.5 text-brand-600" />{{ __('shop.step_fast') }}</dt>
            <dd class="tnum mt-1.5 font-display font-semibold text-navy-900">
              {{ __('shop.within_minutes', ['count' => $product->fulfillment_sla_minutes]) }}</dd>
          </div>
          <div class="panel p-3.5">
            <dt class="flex items-center gap-1.5 text-[11.5px] text-navy-500"><x-store.ui name="shield"
                class="w-3.5 h-3.5 text-seat-600" />{{ __('shop.warranty') }}</dt>
            <dd class="tnum mt-1.5 font-display font-semibold text-navy-900">
              {{ $product->warranty_days ? __('shop.stat_days', ['count' => $product->warranty_days]) : __('shop.support_cover') }}</dd>
          </div>
          <div class="panel p-3.5">
            <dt class="flex items-center gap-1.5 text-[11.5px] text-navy-500"><x-store.ui name="headset"
                class="w-3.5 h-3.5 text-navy-600" />{{ __('store.support') }}</dt>
            <dd class="mt-1.5 font-display font-semibold text-navy-900 text-sm leading-tight">{{ $store->st($store->general(), 'support_hours') }}</dd>
          </div>
        </dl>
      </div>
    </div>

    {{-- Description, how the grant actually happens, and the questions people ask. --}}
    <div class="reveal panel mt-8" x-data="{ t: 'desc' }">
      <div class="flex gap-1 p-2 border-b border-navy-100 overflow-x-auto no-scrollbar">
        <button @click="t='desc'" class="shrink-0 h-10 px-4 rounded-[var(--radius-control)] text-sm font-semibold transition"
          :class="t === 'desc' ? 'bg-navy-900 text-white' : 'text-navy-600 hover:bg-navy-50'">{{ __('shop.description') }}</button>
        <button @click="t='delivery'" class="shrink-0 h-10 px-4 rounded-[var(--radius-control)] text-sm font-semibold transition"
          :class="t === 'delivery' ? 'bg-navy-900 text-white' : 'text-navy-600 hover:bg-navy-50'">{{ __('shop.delivery_instructions') }}</button>
        @if ($product->faqs->isNotEmpty())
          <button @click="t='faq'" class="shrink-0 h-10 px-4 rounded-[var(--radius-control)] text-sm font-semibold transition"
            :class="t === 'faq' ? 'bg-navy-900 text-white' : 'text-navy-600 hover:bg-navy-50'">{{ __('shop.faqs') }}</button>
        @endif
      </div>

      <div class="p-6 md:p-8">
        <div x-show="t==='desc'" x-transition.opacity class="prose-store">{!! clean($product->t('description')) !!}</div>

        <div x-show="t==='delivery'" x-cloak x-transition.opacity class="grid md:grid-cols-3 gap-4">
          @foreach ([['1', __('shop.step_pay'), __('shop.choose_payment_hint'), 'card'], ['2', __('shop.step_invite'), $deliveryStep, 'send'], ['3', __('shop.step_accept'), $product->platform?->invite_instructions ?: __('shop.step_accept_hint'), 'check-circle']] as [$n, $title, $body, $icon])
            <div class="relative panel p-5 bg-navy-50/60">
              <span class="grid place-items-center w-10 h-10 rounded-[var(--radius-control)] bg-navy-900 text-white"><x-store.ui :name="$icon"
                  class="w-[18px] h-[18px]" /></span>
              <span class="tnum absolute top-4 right-5 font-display text-3xl font-bold text-navy-200 select-none">{{ $n }}</span>
              <h3 class="mt-3.5 font-display font-semibold text-navy-900">{{ $title }}</h3>
              <p class="mt-1 text-sm text-navy-600 leading-relaxed">{{ $body }}</p>
            </div>
          @endforeach
        </div>

        @if ($product->faqs->isNotEmpty())
          <div x-show="t==='faq'" x-cloak x-transition.opacity class="divide-y divide-navy-100">
            @foreach ($product->faqs as $faq)
              <details class="group py-4">
                <summary class="flex justify-between items-center gap-4 cursor-pointer font-semibold text-navy-900 list-none">{{ $faq->t('question') }}
                  <span
                    class="grid place-items-center w-7 h-7 rounded-full bg-navy-100 text-navy-600 shrink-0 transition group-open:rotate-180
                    group-open:bg-brand-600 group-open:text-white"><x-store.ui
                      name="chevron-down" class="w-4 h-4" /></span>
                </summary>
                <p class="mt-2 text-navy-600 leading-relaxed">{{ $faq->t('answer') }}</p>
              </details>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    @if ($related->isNotEmpty())
      <section class="mt-14">
        <x-store.section-head :title="__('shop.you_may_also_like')" :href="route('category.show', $product->category)" />
        <div class="reveal stagger mt-6 grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
          @foreach ($related as $item)
            <x-store.product-card :product="$item" />
          @endforeach
        </div>
      </section>
    @endif
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
