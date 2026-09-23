@props(['product'])

@php
    $discount = $product->discountPercent();
    $inStock = $product->isInStock();
    $track = app(\App\Services\TrackingService::class)->product($product);
@endphp

    <article class="group relative bg-white rounded-2xl shadow-card shadow-card-hover ring-1 ring-black/5 overflow-hidden flex flex-col transition hover:-translate-y-0.5">
      @if (filled($product->t('badge_text')))<div class="ribbon {{ $product->badgeClass() }}"><span>{{ $product->t('badge_text') }}</span></div>@else
      @endif
      <a href="{{ route('product.show', $product) }}" class="block p-3 pb-0"><div class="aspect-[4/3] rounded-xl bg-gradient-to-br {{ $product->gradient }} art-rings relative grid place-items-center overflow-hidden">
        @if ($product->image)
        <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->t('name') }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
        @endif
        <span class="grid place-items-center w-24 h-24 rounded-3xl bg-white/15 ring-1 ring-white/25 backdrop-blur-sm text-white rotate-[-6deg] group-hover:rotate-0 group-hover:scale-105 transition duration-300"><x-store.icon :name="$product->icon_key" stroke-width="1.6" class="w-14 h-14" /></span>
        <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-black/25 text-white text-[10px] font-semibold tracking-wide uppercase">{{ $product->category?->name }}</span>
      </div></a>
      <div class="p-4 pt-3 flex-1 flex flex-col">
        <h3 class="font-display font-semibold text-[15px] leading-snug line-clamp-2 min-h-[2.6em]"><a href="{{ route('product.show', $product) }}" class="hover:text-brand-700">{{ $product->t('name') }}</a></h3>
        <div class="mt-3 flex items-end justify-between gap-2">
          <div><p class="text-xl font-display font-bold text-brand-700 leading-none">{{ \App\Support\Money::format($product->price) }}</p>
            @if ($discount)<p class="mt-1 text-xs text-stone-500"><s>{{ \App\Support\Money::format((int) $product->regular_price) }}</s> <span class="text-emerald-700 font-semibold">-{{ $discount }}%</span></p>@endif</div>
          @if ($inStock)
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 text-xs font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ __('shop.pcs', ['count' => $product->stock]) }}</span>
          @else
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 ring-1 ring-red-600/20 text-xs font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ __('shop.pcs', ['count' => 0]) }}</span>
          @endif
        </div>
        <div class="mt-auto">@if ($inStock)<div class="mt-4 grid grid-cols-[auto_1fr] gap-2"><button type="button" @click="window.Livewire.dispatch('quick-add', { slug: '{{ $product->slug }}' })" data-track-click="add_to_cart" data-track-payload='@json($track)' class="h-11 px-3 inline-flex items-center gap-2 rounded-xl ring-1 ring-brand-600 text-brand-700 font-semibold hover:bg-brand-50" aria-label="{{ __('shop.add_to_cart') }} — {{ $product->t('name') }}"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg></button><button type="button" @click="window.Livewire.dispatch('open-buy', { slug: '{{ $product->slug }}' })" class="h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-md shadow-brand-700/25 hover:from-brand-700 hover:to-brand-900 active:scale-[.98] transition">{{ __('shop.buy_now') }} <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg></button></div>@else<button type="button" disabled class="mt-4 w-full h-11 rounded-xl bg-stone-100 text-stone-400 font-semibold cursor-not-allowed">{{ __('shop.out_of_stock') }}</button>@endif</div>
      </div>
    </article>
