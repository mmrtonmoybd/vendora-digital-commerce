@props(['product'])

{{--
  A pass.

  What this shop issues is permission, so the card is shaped like the thing that gets
  issued: an artwork panel, a perforation punched through the card's own outline, and a stub
  below carrying the terms. Everything on the stub is read off the record — price, seats
  left, how long the grant takes, how long it is covered — which is why it is set as ruled
  terms in tabular figures rather than as marketing copy.
--}}
@php
  $discount = $product->discountPercent();
  $inStock = $product->isInStock();
  $track = app(\App\Services\TrackingService::class)->product($product);
  $sla = (int) ($product->fulfillment_sla_minutes ?? 0);
  $warranty = (int) ($product->warranty_days ?? 0);
@endphp

<article class="reveal group pass pass-lift relative flex flex-col" style="--tear: 232px">
  @if (filled($product->t('badge_text')))
    <div class="ribbon {{ $product->badgeClass() }}"><span>{{ $product->t('badge_text') }}</span></div>
  @endif

  @if ($discount)
    <span
      class="tnum absolute top-3 right-3 z-[5] inline-flex items-center h-[22px] px-2 rounded-[2px] bg-ink-900 font-display text-[11px] font-bold text-white">−{{ $discount }}%</span>
  @endif

  {{-- The artwork panel, 200px tall, so the perforation lands in the same place on every
       card in a row however long the names are. --}}
  <a href="{{ route('product.show', $product) }}" class="block" tabindex="-1" aria-hidden="true">
    <div class="well h-[200px] rounded-t-[var(--radius-panel)]">
      @if ($product->image)
        <img src="{{ Storage::disk('public')->url($product->image) }}" alt="" loading="lazy"
          class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
      @else
        <span class="relative grid place-items-center w-20 h-20 rounded-[var(--radius-panel)] bg-ink-900 text-white zoom-in-hover">
          <x-store.icon :name="$product->icon_key" stroke-width="1.6" class="w-10 h-10" /></span>
      @endif
    </div>
  </a>

  {{-- The counterfoil: who issues it, and for what. --}}
  <div class="px-4 pt-3 pb-4">
    @if ($product->platform)
      <p class="flex items-center gap-1.5 text-[11.5px] font-medium text-ink-500">
        <x-store.ui name="layers" class="w-3.5 h-3.5 text-ink-400" />{{ $product->platform->t('name') }}
      </p>
    @endif

    <h3 class="mt-1 font-display text-[15px] font-bold leading-snug text-ink-900 line-clamp-2 min-h-[2.6em]">
      <a href="{{ route('product.show', $product) }}" class="hover:text-brand-700 transition">{{ $product->t('name') }}</a>
    </h3>
  </div>

  {{-- The tear, and below it the stub. --}}
  <div class="tear mt-auto px-4 pt-4 pb-4 flex-1 flex flex-col">
    <div class="flex items-baseline gap-2">
      <span class="tnum font-display text-[24px] font-extrabold leading-none text-ink-900">{{ \App\Support\Money::format($product->price) }}</span>
      @if ($discount)
        <span class="tnum text-xs text-ink-400"><s>{{ \App\Support\Money::format((int) $product->regular_price) }}</s></span>
      @endif
    </div>

    <dl class="mt-3 space-y-0.5 text-[12px]">
      <div class="kv py-0.5">
        <dt>{{ __('shop.seats_label') }}</dt>
        <dd class="tnum flex items-center gap-1.5 {{ $inStock ? '' : 'text-brand-700' }}">
          @if ($inStock)
            <span class="live-dot"></span>
          @endif
          {{ $inStock ? __('shop.seats_left', ['count' => $product->stock]) : __('shop.out_of_stock') }}
        </dd>
      </div>
      @if ($sla > 0 && $inStock)
        <div class="kv py-0.5">
          <dt>{{ __('shop.delivery_window') }}</dt>
          <dd class="tnum">{{ __('shop.within_minutes', ['count' => $sla]) }}</dd>
        </div>
      @endif
      @if ($warranty > 0)
        <div class="kv py-0.5">
          <dt>{{ __('shop.warranty') }}</dt>
          <dd class="tnum">{{ __('shop.stat_days', ['count' => $warranty]) }}</dd>
        </div>
      @endif
    </dl>

    <div class="mt-4">
      @if ($inStock)
        <div class="grid grid-cols-[auto_1fr] gap-2">
          <button type="button" @click="window.Livewire.dispatch('quick-add', { slug: '{{ $product->slug }}' })" data-track-click="add_to_cart"
            data-track-payload='@json($track)' class="btn btn-quiet w-10 px-0"
            aria-label="{{ __('shop.add_to_cart') }} — {{ $product->t('name') }}"><x-store.ui name="plus" class="w-4 h-4" /></button>
          <button type="button" @click="window.Livewire.dispatch('buy-now', { slug: '{{ $product->slug }}' })"
            class="btn btn-primary sheen">{{ __('shop.buy_now') }}<x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></button>
        </div>
      @else
        <button type="button" disabled class="btn btn-block" aria-disabled="true">{{ __('shop.out_of_stock') }}</button>
      @endif
    </div>
  </div>
</article>
