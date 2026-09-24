  <div class="wrap grid lg:grid-cols-[1fr_380px] gap-6 items-start">
    <div class="panel overflow-hidden">
      <div class="panel-head">
        <h2 class="h-panel flex items-center gap-2.5"><x-store.ui name="cart" class="w-[18px] h-[18px] text-navy-400" />
          {{ __('shop.items_count', ['count' => $count]) }}</h2>
        @if ($items->isNotEmpty())
          <button type="button" wire:click="clear" class="group inline-flex items-center gap-1.5 text-sm text-navy-500 hover:text-brand-700 transition">
            <x-store.ui name="trash" class="w-4 h-4 icon-rise" />{{ __('shop.clear_cart') }}</button>
        @endif
      </div>

      @forelse ($items as $item)
        @continue(!$item->product)
        <div class="p-5 md:px-6 flex gap-4 border-b border-navy-100 last:border-0" wire:key="item-{{ $item->id }}">
          <a href="{{ route('product.show', $item->product) }}"
            class="well shrink-0 w-20 h-20 rounded-[var(--radius-control)] border border-navy-100">
            @if ($item->product->image)
              <img src="{{ Storage::disk('public')->url($item->product->image) }}" alt="" loading="lazy"
                class="absolute inset-0 w-full h-full object-fill">
            @else
              <x-store.icon :name="$item->product->icon_key" stroke-width="1.6" class="w-8 h-8 text-navy-400" />
            @endif
          </a>

          <div class="flex-1 min-w-0">
            <a href="{{ route('product.show', $item->product) }}"
              class="font-display font-semibold text-navy-900 hover:text-brand-700 transition line-clamp-2">{{ $item->product->name }}</a>
            <p class="mt-0.5 text-xs text-navy-500">{{ $item->product->category?->name }}</p>

            @foreach ($item->fields ?? [] as $key => $value)
              @if (filled($value))
                <p class="text-xs text-navy-600"><span class="capitalize">{{ str_replace('_', ' ', $key) }}</span>: <b>{{ $value }}</b></p>
              @endif
            @endforeach

            <div class="mt-3 flex flex-wrap items-center gap-3">
              <div class="inline-flex items-center rounded-[var(--radius-control)] border border-navy-200 overflow-hidden">
                <button type="button" wire:click="setQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                  class="w-9 h-9 grid place-items-center text-navy-600 hover:bg-navy-50 transition" aria-label="{{ __('store.decrease') }}">
                  <x-store.ui name="minus" class="w-4 h-4" /></button>
                <span class="tnum w-10 text-center font-semibold text-navy-900">{{ $item->quantity }}</span>
                <button type="button" wire:click="setQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                  class="w-9 h-9 grid place-items-center text-navy-600 hover:bg-navy-50 transition" aria-label="{{ __('store.increase') }}">
                  <x-store.ui name="plus" class="w-4 h-4" /></button>
              </div>
              <button type="button" wire:click="remove({{ $item->id }})"
                class="group inline-flex items-center gap-1.5 text-sm text-navy-500 hover:text-brand-700 transition">
                <x-store.ui name="trash" class="w-4 h-4 icon-rise" />{{ __('store.remove') }}</button>
            </div>
          </div>

          <div class="text-right shrink-0">
            <p class="tnum font-display font-bold text-navy-900">{{ $this->money($item->lineTotal()) }}</p>
            <p class="tnum text-xs text-navy-500">{{ $this->money($item->product->price) }} {{ __('shop.each') }}</p>
          </div>
        </div>
      @empty
        <div class="py-16 px-6 text-center">
          <span class="inline-grid place-items-center w-16 h-16 rounded-[var(--radius-panel)] bg-navy-50 text-navy-400 float">
            <x-store.ui name="cart" class="w-7 h-7" /></span>
          <h3 class="mt-4 font-display text-xl font-semibold text-navy-900">{{ __('shop.your_cart_is_empty') }}</h3>
          <p class="mt-1 text-navy-500">{{ __('shop.cart_empty_hint') }}</p>
          <a href="{{ route('home') }}#categories" class="btn btn-primary mt-6 sheen">{{ __('shop.start_shopping') }}
            <x-store.ui name="arrow-right" class="w-4 h-4" /></a>
        </div>
      @endforelse
    </div>

    <aside class="panel panel-p lg:sticky lg:top-6">
      <h2 class="h-panel">{{ __('shop.order_summary') }}</h2>

      <div class="mt-4 flex gap-2">
        <label class="relative flex-1">
          <x-store.ui name="tag" class="field-affix w-4 h-4" />
          <input wire:model="coupon" placeholder="{{ __('shop.coupon_code') }}" class="field field-icon field-code">
        </label>
        <button type="button" wire:click="applyCoupon" class="btn btn-dark">{{ __('store.apply') }}</button>
      </div>
      @if ($couponMsg)
        <p class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold {{ $couponOk ? 'text-seat-700' : 'text-brand-700' }}">
          <x-store.ui :name="$couponOk ? 'check-circle' : 'alert'" class="w-3.5 h-3.5" />{{ $couponMsg }}
        </p>
      @endif

      <dl class="mt-5 divide-y divide-navy-100">
        <div class="kv">
          <dt>{{ __('shop.subtotal') }}</dt>
          <dd class="tnum">{{ $this->money($subtotal) }}</dd>
        </div>
        @if ($couponOk)
          <div class="kv text-seat-700">
            <dt class="text-seat-700">{{ __('shop.discount') }}</dt>
            <dd class="tnum text-seat-700">− {{ $this->money($discount) }}</dd>
          </div>
        @endif
        <div class="kv">
          <dt>{{ __('shop.delivery') }}</dt>
          <dd class="text-seat-700">{{ __('shop.free_digital') }}</dd>
        </div>
      </dl>

      <div class="mt-3 pt-4 rule-dashed flex items-end justify-between">
        <span class="font-semibold text-navy-900">{{ __('shop.total') }}</span>
        <span class="tnum font-display text-2xl font-bold text-navy-900">{{ $this->money($total) }}</span>
      </div>

      <a href="{{ route('checkout') }}"
        class="btn btn-primary btn-lg btn-block sheen group mt-5 {{ $items->isEmpty() ? 'pointer-events-none opacity-50' : '' }}">
        {{ __('shop.proceed_to_checkout') }}<x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
      <a href="{{ route('home') }}#categories" class="btn btn-ghost btn-block mt-2">{{ __('store.continue_shopping') }}</a>

      @guest
        {{-- Said here rather than discovered at the checkout door. The cart survives the
             detour: it is merged into the account's cart at sign-in. --}}
        <p class="mt-3 flex items-start gap-2 text-xs text-navy-500">
          <x-store.ui name="info" class="w-3.5 h-3.5 mt-0.5 shrink-0" />
          <span>{{ __('shop.sign_in_to_order') }} <a href="{{ route('login') }}" class="link">{{ __('account.sign_in') }}</a></span>
        </p>
      @endguest
    </aside>
  </div>
