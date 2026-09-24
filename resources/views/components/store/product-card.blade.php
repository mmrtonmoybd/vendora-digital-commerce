@props(['product'])

{{--
  A product card.

  What this shop sells is access granted by a person, so the card leads with the facts a
  buyer actually weighs: what it costs, how many seats are left, how long fulfilment takes
  and which platform the access arrives through. Those come from the record — they are not
  marketing copy — which is why they are set in tabular figures and given their own row
  rather than buried under the title.

  The artwork sits in a near-white well and grows a little under the cursor; the card lifts
  and its edge picks up the accent. Everything else holds still.
--}}
@php
  $discount = $product->discountPercent();
  $inStock = $product->isInStock();
  $track = app(\App\Services\TrackingService::class)->product($product);
  $sla = (int) ($product->fulfillment_sla_minutes ?? 0);
  $warranty = (int) ($product->warranty_days ?? 0);
@endphp

<article class="reveal group panel panel-hover edge-glow relative overflow-hidden flex flex-col">
  @if (filled($product->t('badge_text')))
    <div class="ribbon {{ $product->badgeClass() }}"><span>{{ $product->t('badge_text') }}</span></div>
  @endif

  @if ($discount)
    <span
      class="tnum absolute top-3 right-3 z-[5] inline-flex items-center h-6 px-2 rounded-md bg-navy-900 text-white text-[11px] font-semibold">−{{ $discount }}%</span>
  @endif

  <a href="{{ route('product.show', $product) }}" class="block" tabindex="-1" aria-hidden="true">
    <div class="well aspect-[5/4] border-b border-navy-100">
      @if ($product->image)
        <img src="{{ Storage::disk('public')->url($product->image) }}" alt="" loading="lazy"
          class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
      @else
        <span class="relative grid place-items-center w-20 h-20 rounded-2xl bg-navy-900 text-white zoom-in-hover"><x-store.icon :name="$product->icon_key"
            stroke-width="1.6" class="w-10 h-10" /></span>
      @endif
    </div>
  </a>

  <div class="p-4 flex-1 flex flex-col">
    @if ($product->platform)
      <p class="flex items-center gap-1.5 text-[11.5px] font-medium text-navy-500"><x-store.ui name="layers"
          class="w-3.5 h-3.5 text-navy-400" />{{ $product->platform->t('name') }}</p>
    @endif

    <h3 class="mt-1 font-display font-semibold text-[15px] leading-snug text-navy-900 line-clamp-2 min-h-[2.6em]"><a
        href="{{ route('product.show', $product) }}" class="hover:text-brand-700 transition">{{ $product->t('name') }}</a></h3>

    <div class="mt-3 flex items-baseline gap-2">
      <span class="tnum font-display text-[22px] font-bold text-navy-900 leading-none">{{ \App\Support\Money::format($product->price) }}</span>
      @if ($discount)
        <span class="tnum text-xs text-navy-400"><s>{{ \App\Support\Money::format((int) $product->regular_price) }}</s></span>
      @endif
    </div>

    {{-- The record, in one row: what is left, how fast it lands, how long it is covered. --}}
    <dl class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11.5px] text-navy-500">
      <div class="flex items-center gap-1.5">
        @if ($inStock)
          <span class="live-dot"></span>
        @else
          <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
        @endif
        <dt class="sr-only">{{ __('shop.seats_label') }}</dt>
        <dd class="tnum font-medium {{ $inStock ? 'text-navy-700' : 'text-brand-700' }}">
          {{ $inStock ? __('shop.seats_left', ['count' => $product->stock]) : __('shop.out_of_stock') }}</dd>
      </div>
      @if ($sla > 0 && $inStock)
        <div class="flex items-center gap-1.5"><x-store.ui name="clock" class="w-3.5 h-3.5 text-navy-400" />
          <dt class="sr-only">{{ __('shop.delivery_window') }}</dt>
          <dd class="tnum">{{ __('shop.within_minutes', ['count' => $sla]) }}</dd>
        </div>
      @endif
      @if ($warranty > 0)
        <div class="flex items-center gap-1.5"><x-store.ui name="shield" class="w-3.5 h-3.5 text-navy-400" />
          <dt class="sr-only">{{ __('shop.warranty') }}</dt>
          <dd class="tnum">{{ __('shop.stat_days', ['count' => $warranty]) }}</dd>
        </div>
      @endif
    </dl>

    <div class="mt-4 pt-4 rule">
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
