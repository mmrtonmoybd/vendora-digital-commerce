  <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-[1fr_380px] gap-6 items-start">
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 overflow-hidden">
      <div class="px-5 md:px-6 py-4 border-b border-stone-100 flex items-center justify-between">
        <h2 class="font-display font-semibold text-lg">{{ __('shop.items_count', ['count' => $count]) }}</h2>
        @if ($items->isNotEmpty())
          <button type="button" wire:click="clear" class="text-sm text-stone-500 hover:text-brand-700">{{ __('shop.clear_cart') }}</button>
        @endif
      </div>
      @forelse ($items as $item)
        @continue(!$item->product)
        <div class="p-5 md:px-6 flex gap-4 border-b border-stone-100 last:border-0" wire:key="item-{{ $item->id }}">
          <span class="shrink-0 grid place-items-center w-20 h-20 rounded-2xl bg-gradient-to-br text-white {{ $item->product->gradient }}"><x-store.icon
              :name="$item->product->icon_key" stroke-width="1.6" class="w-8 h-8" /></span>
          <div class="flex-1 min-w-0">
            <a href="{{ route('product.show', $item->product) }}"
              class="font-display font-semibold hover:text-brand-700 line-clamp-2">{{ $item->product->name }}</a>
            <p class="text-xs text-stone-500 mt-0.5">{{ $item->product->category?->name }}</p>
            @foreach ($item->fields ?? [] as $key => $value)
              @if (filled($value))
                <p class="text-xs text-stone-600"><span class="capitalize">{{ str_replace('_', ' ', $key) }}</span>: <b>{{ $value }}</b></p>
              @endif
            @endforeach
            <div class="mt-3 flex flex-wrap items-center gap-3">
              <div class="inline-flex items-center rounded-xl ring-1 ring-stone-200">
                <button type="button" wire:click="setQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                  class="w-9 h-9 grid place-items-center hover:bg-brand-50" aria-label="{{ __('store.decrease') }}"><svg class="w-4 h-4" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14" />
                  </svg></button>
                <span class="w-10 text-center font-semibold">{{ $item->quantity }}</span>
                <button type="button" wire:click="setQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                  class="w-9 h-9 grid place-items-center hover:bg-brand-50" aria-label="{{ __('store.increase') }}"><svg class="w-4 h-4" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                  </svg></button>
              </div>
              <button type="button" wire:click="remove({{ $item->id }})" class="inline-flex items-center gap-1 text-sm text-stone-500 hover:text-red-600"
                aria-label="{{ __('store.remove') }}"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 6h18" />
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                  <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg> Remove</button>
            </div>
          </div>
          <div class="text-right">
            <p class="font-display font-bold text-brand-700">{{ $this->money($item->lineTotal()) }}</p>
            <p class="text-xs text-stone-500">{{ $this->money($item->product->price) }} {{ __('shop.each') }}</p>
          </div>
        </div>
      @empty
        <div class="py-16 text-center px-6">
          <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-brand-50 text-brand-600"><svg class="w-7 h-7" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="8" cy="21" r="1" />
              <circle cx="19" cy="21" r="1" />
              <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
            </svg></span>
          <h3 class="mt-4 font-display text-xl font-bold">{{ __('shop.your_cart_is_empty') }}</h3>
          <p class="text-stone-600">{{ __('shop.cart_empty_hint') }}</p>
          <a href="{{ route('home') }}#categories"
            class="mt-5 inline-flex h-11 px-5 items-center rounded-full bg-brand-600 text-white font-semibold">{{ __('shop.start_shopping') }}</a>
        </div>
      @endforelse
    </div>

    <aside class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-5 md:p-6 lg:sticky lg:top-6">
      <h2 class="font-display font-semibold text-lg">{{ __('shop.order_summary') }}</h2>
      <div class="mt-4 flex gap-2"><input wire:model="coupon" placeholder="{{ __('shop.coupon_code') }}"
          class="flex-1 h-11 rounded-xl border-stone-200 uppercase placeholder:normal-case focus:border-brand-500 focus:ring-brand-500"><button type="button"
          wire:click="applyCoupon" class="h-11 px-4 rounded-xl bg-ink text-white font-semibold">{{ __('store.apply') }}</button></div>
      @if ($couponMsg)
        <p class="mt-1 text-xs font-semibold {{ $couponOk ? 'text-emerald-700' : 'text-red-600' }}">{{ $couponMsg }}</p>
      @endif
      <dl class="mt-5 space-y-2 text-sm">
        <div class="flex justify-between">
          <dt class="text-stone-600">{{ __('shop.subtotal') }}</dt>
          <dd class="font-semibold">{{ $this->money($subtotal) }}</dd>
        </div>
        @if ($couponOk)
          <div class="flex justify-between text-emerald-700">
            <dt>{{ __('shop.discount') }}</dt>
            <dd class="font-semibold">− {{ $this->money($discount) }}</dd>
          </div>
        @endif
        <div class="flex justify-between">
          <dt class="text-stone-600">{{ __('shop.delivery') }}</dt>
          <dd class="font-semibold text-emerald-700">{{ __('shop.free_digital') }}</dd>
        </div>
        <div class="flex justify-between items-end pt-3 border-t border-dashed border-stone-300">
          <dt class="font-semibold">{{ __('shop.total') }}</dt>
          <dd class="font-display text-2xl font-bold text-brand-700">{{ $this->money($total) }}</dd>
        </div>
      </dl>
      <a href="{{ route('checkout') }}"
        class="{{ $items->isEmpty() ? 'pointer-events-none opacity-50' : '' }} mt-5 w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/25">{{ __('shop.proceed_to_checkout') }}
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
          aria-hidden="true">
          <path d="M5 12h14" />
          <path d="m12 5 7 7-7 7" />
        </svg></a>
      <a href="{{ route('home') }}#categories"
        class="mt-2 w-full h-11 inline-flex items-center justify-center rounded-xl text-sm font-semibold text-stone-600 hover:bg-stone-50">{{ __('store.continue_shopping') }}</a>
    </aside>
  </div>
