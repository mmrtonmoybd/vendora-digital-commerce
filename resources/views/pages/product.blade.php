@php
  $discount = $product->discountPercent();
  $inStock = $product->isInStock();
  $gallery = collect($product->gallery ?? [])->take(4);
  $track = app(\App\Services\TrackingService::class)->product($product);
  $sla = (int) ($product->fulfillment_sla_minutes ?? 0);
  $warranty = (int) ($product->warranty_days ?? 0);

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
    {{--
      The pass at full size. The card in a listing is this document's stub; here the artwork
      gets its own half and the right-hand half carries the decision — who issues it, what it
      costs, and then, below the perforation, the terms of the grant itself.
    --}}
    <div class="reveal panel p-4 md:p-8 grid lg:grid-cols-2 gap-8 lg:gap-12">
      <div class="group relative">
        @if (filled($product->t('badge_text')))
          <div class="ribbon {{ $product->badgeClass() }}"><span>{{ $product->t('badge_text') }}</span></div>
        @endif

        <div class="well aspect-square md:aspect-[5/4] rounded-[var(--radius-panel)] border border-ink-100">
          @if ($product->image)
            <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->t('name') }}"
              class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
          @else
            <span class="relative grid place-items-center w-28 h-28 rounded-[var(--radius-panel)] bg-ink-900 text-white float">
              <x-store.icon :name="$product->icon_key" stroke-width="1.5" class="w-14 h-14" /></span>
          @endif

          @if ($product->category)
            <a href="{{ route('category.show', $product->category) }}"
              class="tap absolute bottom-3 left-3 inline-flex items-center gap-1.5 h-7 px-2.5 rounded-[var(--radius-control)] bg-ink-950/75 text-white text-[11.5px] font-medium backdrop-blur-sm">
              <x-store.icon :name="$product->category->icon_key" class="w-3.5 h-3.5" />{{ $product->category->t('name') }}</a>
          @endif
        </div>

        @if ($gallery->isNotEmpty())
          <div class="mt-3 grid grid-cols-4 gap-2">
            @foreach ($gallery as $index => $image)
              <span
                class="well aspect-square rounded-[var(--radius-control)] border {{ $index === 0 ? 'border-brand-500' : 'border-ink-200' }}">
                <img src="{{ Storage::disk('public')->url($image) }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-fill">
              </span>
            @endforeach
          </div>
        @endif
      </div>

      <div>
        @if ($product->platform)
          <p class="flex items-center gap-2 text-sm text-ink-500"><x-store.ui name="layers"
              class="w-4 h-4 text-ink-400" />{{ $product->platform->t('name') }}</p>
        @endif

        <h2 class="mt-2 font-display text-[1.75rem] md:text-[2.125rem] font-bold leading-[1.12] tracking-[-0.02em] text-ink-900">
          {{ $product->t('name') }}</h2>

        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
          <span class="flex items-center gap-1 text-brand-600">
            @for ($i = 0; $i < $product->ratingStars(); $i++)
              <x-store.ui name="star" class="w-4 h-4 fill-current" stroke-width="1" />
            @endfor
          </span>
          <span class="tnum text-ink-500">{{ $product->ratingValue() }} · {{ number_format($product->reviews_count) }} {{ __('shop.reviews') }}</span>
          @if ($inStock)
            <span class="flag flag-live"><span class="live-dot"></span>{{ __('shop.seats_left', ['count' => $product->stock]) }}</span>
          @else
            <span class="flag flag-off">{{ __('shop.out_of_stock') }}</span>
          @endif
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-3">
          <span class="tnum font-display text-[2.5rem] font-bold leading-none text-ink-900">{{ \App\Support\Money::format($product->price) }}</span>
          @if ($discount)
            <s class="tnum text-lg text-ink-400">{{ \App\Support\Money::format((int) $product->regular_price) }}</s>
            <span class="tnum flag flag-live">{{ __('shop.save_percent', ['percent' => $discount]) }}</span>
          @endif
        </div>

        <p class="mt-4 text-ink-600 leading-relaxed max-w-[60ch]">{{ $product->t('short') }}</p>

        @if ($product->featureList())
          <ul class="mt-5 grid sm:grid-cols-2 gap-2.5">
            @foreach ($product->featureList() as $feature)
              <li class="flex items-start gap-2 text-sm text-ink-700">
                <span class="grid place-items-center w-5 h-5 rounded-full bg-grant-50 text-grant-700 shrink-0 mt-px"><x-store.ui name="check" class="w-3 h-3"
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

        {{--
          The stub, below the perforation: what is actually being committed to. These are read
          off the record — seats, the window the grant lands in, how long it stays covered —
          so they are set as ruled terms with a dotted leader rather than as three claims in
          icon boxes.
        --}}
        <div class="tear mt-7 pt-5">
          <p class="label-line">{{ __('shop.grant_terms') }}</p>

          <dl class="mt-2">
            <div class="kv">
              <dt>{{ __('shop.seats_label') }}</dt>
              <dd class="tnum flex items-center gap-1.5 {{ $inStock ? '' : 'text-brand-700' }}">
                @if ($inStock)
                  <span class="live-dot"></span>
                @endif
                {{ $inStock ? __('shop.seats_left', ['count' => $product->stock]) : __('shop.out_of_stock') }}
              </dd>
            </div>
            @if ($sla > 0)
              <div class="kv">
                <dt>{{ __('shop.delivery_window') }}</dt>
                <dd class="tnum">{{ __('shop.within_minutes', ['count' => $sla]) }}</dd>
              </div>
            @endif
            <div class="kv">
              <dt>{{ __('shop.warranty') }}</dt>
              <dd class="tnum">{{ $warranty > 0 ? __('shop.stat_days', ['count' => $warranty]) : __('shop.support_cover') }}</dd>
            </div>
            <div class="kv">
              <dt>{{ __('store.support') }}</dt>
              <dd>{{ $store->st($store->general(), 'support_hours') }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    {{-- Description, how the grant actually happens, and the questions people ask. --}}
    <div class="reveal panel mt-8" x-data="{ t: 'desc' }">
      <div class="flex gap-2 p-2 border-b border-ink-100 overflow-x-auto no-scrollbar">
        <button @click="t='desc'" class="chip" :class="t === 'desc' ? 'chip-on' : ''">{{ __('shop.description') }}</button>
        <button @click="t='delivery'" class="chip" :class="t === 'delivery' ? 'chip-on' : ''">{{ __('shop.delivery_instructions') }}</button>
        @if ($product->faqs->isNotEmpty())
          <button @click="t='faq'" class="chip" :class="t === 'faq' ? 'chip-on' : ''">{{ __('shop.faqs') }}</button>
        @endif
      </div>

      <div class="p-6 md:p-8">
        <div x-show="t==='desc'" x-transition.opacity class="prose-store">{!! clean($product->t('description')) !!}</div>

        {{-- The grant is a sequence of things people do, so it is set as a numbered docket
             rather than as three cards standing side by side pretending to be features. --}}
        <div x-show="t==='delivery'" x-cloak x-transition.opacity>
          <ol class="max-w-[72ch]">
            @foreach ([['1', __('shop.step_pay'), __('shop.choose_payment_hint')], ['2', __('shop.step_invite'), $deliveryStep], ['3', __('shop.step_accept'), $product->platform?->invite_instructions ?: __('shop.step_accept_hint')]] as [$n, $title, $body])
              <li class="flex gap-4 {{ $loop->first ? 'pb-4' : 'rule-dashed py-4' }}">
                <span class="tnum shrink-0 w-5 font-display text-[15px] font-bold text-brand-700">{{ $n }}</span>
                <div>
                  <h3 class="font-display font-semibold text-ink-900">{{ $title }}</h3>
                  <p class="mt-1 text-sm text-ink-600 leading-relaxed">{{ $body }}</p>
                </div>
              </li>
            @endforeach
          </ol>
        </div>

        @if ($product->faqs->isNotEmpty())
          <div x-show="t==='faq'" x-cloak x-transition.opacity class="divide-y divide-ink-100">
            @foreach ($product->faqs as $faq)
              <details class="group py-4">
                <summary class="flex justify-between items-center gap-4 cursor-pointer font-semibold text-ink-900 list-none">{{ $faq->t('question') }}
                  <span
                    class="grid place-items-center w-7 h-7 rounded-full bg-ink-100 text-ink-600 shrink-0 transition group-open:rotate-180
                    group-open:bg-brand-600 group-open:text-white"><x-store.ui
                      name="chevron-down" class="w-4 h-4" /></span>
                </summary>
                <p class="mt-2 text-ink-600 leading-relaxed">{{ $faq->t('answer') }}</p>
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
