<div>
@if ($show && $product)
<!-- ================= BUY NOW MODAL ================= -->
<div x-data x-show="true" class="fixed inset-0 z-[90] flex items-end sm:items-center justify-center sm:p-4" @keydown.escape.window="$wire.close()" role="dialog" aria-modal="true" aria-labelledby="buy-title">
  <div x-transition.opacity class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @click="$wire.close()"></div>
  <div x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95"
       class="relative w-full sm:max-w-xl max-h-[92vh] overflow-y-auto bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl">
    <!-- head -->
    <div class="sticky top-0 z-10 hero-ground text-white px-5 py-4 flex items-center gap-3">
      <span class="grid place-items-center w-12 h-12 rounded-xl bg-white/15 ring-1 ring-white/25 shrink-0 bg-gradient-to-br {{ $product->gradient }}">
        <x-store.icon :name="$product->icon_key" class="w-5 h-5" /></span>
      <div class="min-w-0 flex-1"><h3 id="buy-title" class="font-display font-semibold leading-tight truncate">{{ $product->t('name') }}</h3>
        <p class="text-sm text-white/80"><b class="text-white">{{ $this->money($product->price) }}</b> @if ($product->regular_price)<s class="opacity-70">{{ $this->money((int) $product->regular_price) }}</s> @endif· <span>{{ $product->stock }} in stock</span></p></div>
      <button type="button" wire:click="close" class="grid place-items-center w-9 h-9 rounded-full bg-white/15 hover:bg-white/25" aria-label="{{ __('store.close') }}"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
    </div>

    <form class="p-5 space-y-5" wire:submit="checkout" novalidate>
      <!-- email -->
      <label class="block"><span class="text-sm font-semibold">{{ __('shop.delivery_email') }} <span class="text-brand-600">*</span></span>
        <input type="email" wire:model.blur="email" placeholder="you@example.com" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500">
        <span class="mt-1 block text-xs text-stone-500">{{ $deliveryHelp }}</span></label>

      <!-- qty -->
      <div><span class="text-sm font-semibold">{{ __('shop.quantity') }}</span>
        <div class="mt-1.5 flex items-center gap-3">
          <div class="inline-flex items-center rounded-xl ring-1 ring-stone-200 overflow-hidden">
            <button type="button" wire:click="decrement" @disabled($qty <= $product->min_qty) class="w-11 h-11 grid place-items-center hover:bg-brand-50 disabled:opacity-40" aria-label="{{ __('store.decrease') }}"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/></svg></button>
            <input type="number" wire:model.live.debounce.400ms="qty" class="w-14 h-11 text-center border-0 focus:ring-0 font-semibold" aria-label="{{ __('shop.quantity') }}">
            <button type="button" wire:click="increment" @disabled($qty >= $this->maxQty()) class="w-11 h-11 grid place-items-center hover:bg-brand-50 disabled:opacity-40" aria-label="{{ __('store.increase') }}"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg></button>
          </div>
          <p class="text-xs text-stone-500">{{ __('shop.min') }} <b>{{ $product->min_qty }}</b> · {{ __('shop.max') }} <b>{{ $this->maxQty() }}</b></p>
        </div></div>

      <!-- dynamic product fields -->
      @foreach ($product->fields as $field)
        <label class="block"><span class="text-sm font-semibold"><span>{{ $field->t('label') }}</span> @if ($field->required)<span class="text-brand-600">*</span>@endif</span>
          @if ($field->type === \App\Enums\FieldType::Select)<select wire:model="fields.{{ $field->key }}" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500">@foreach (($field->options ?? []) as $option)<option>{{ $option }}</option>@endforeach</select>
          @elseif ($field->type === \App\Enums\FieldType::Textarea)<textarea wire:model="fields.{{ $field->key }}" rows="2" placeholder="{{ $field->t('placeholder') }}" class="mt-1.5 w-full rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></textarea>
          @else<input type="{{ $field->type->value }}" wire:model="fields.{{ $field->key }}" placeholder="{{ $field->t('placeholder') }}" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500">@endif
        </label>
      @endforeach

      <!-- coupon -->
      <div><span class="text-sm font-semibold">{{ __('shop.coupon_code') }}</span>
        <div class="mt-1.5 flex gap-2">
          <label class="relative flex-1"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5"/></svg></span>
            <input wire:model="coupon" placeholder="{{ __('shop.enter_a_code') }}" class="w-full h-11 pl-9 rounded-xl border-stone-200 uppercase placeholder:normal-case focus:border-brand-500 focus:ring-brand-500"></label>
          <button type="button" wire:click="applyCoupon" class="h-11 px-5 rounded-xl bg-ink text-white font-semibold hover:bg-stone-800">{{ __('store.apply') }}</button>
        </div>
        @if ($couponMsg)<p class="mt-1 text-xs font-semibold {{ $couponOk ? 'text-emerald-700' : 'text-red-600' }}">{{ $couponMsg }}</p>@endif</div>

      <!-- payment -->
      <div><span class="text-sm font-semibold">{{ __('shop.payment_method') }}</span>
        <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-2">
          @foreach ($methods as $method)
            @php $walletBlocked = $method->isWallet() && $this->walletDisabled(); @endphp
            <label class="relative flex items-center gap-2 p-2.5 rounded-xl ring-1 cursor-pointer transition {{ $paymentKey === $method->key ? 'ring-2 ring-brand-600 bg-brand-50' : 'ring-stone-200 hover:ring-stone-300' }} {{ $walletBlocked ? 'opacity-50 cursor-not-allowed' : '' }}">
              <input type="radio" class="sr-only" value="{{ $method->key }}" wire:model.live="paymentKey" @disabled($walletBlocked)>
              <span class="grid place-items-center w-8 h-8 rounded-lg text-white text-[11px] font-bold shrink-0 {{ $method->chip_color }}">{{ $method->t('short') }}</span>
              <span class="text-[13px] font-semibold leading-tight"><span>{{ $method->t('name') }}</span>
                @if ($method->isWallet())<span class="block text-[11px] font-medium text-stone-500">{{ auth()->check() ? $this->money((int) auth()->user()->balance) : 'Sign in to use' }}</span>@endif</span>
            </label>
          @endforeach
        </div>
        @if ($selected = $this->method())
        <div class="mt-3 p-3 rounded-xl bg-amber-50 ring-1 ring-amber-200 text-[13px] text-amber-900">
          <p>{{ $selected->instructions }}</p>
          @if ($selected->account_number)<p class="mt-1.5 font-semibold">{{ __('shop.send_to') }} <span class="font-mono">{{ $selected->account_number }}</span> <span class="text-amber-700 font-normal">({{ $selected->account_type }})</span></p>@endif
        </div>
        @if ($selected->isGateway())
        <label class="block mt-3"><span class="text-sm font-semibold">{{ __('shop.phone_number') }} <span class="text-brand-600">*</span></span>
          <input type="tel" wire:model.blur="customerPhone" placeholder="01XXXXXXXXX" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></label>
        @endif
        @endif</div>

      <!-- totals -->
      <div class="rounded-2xl bg-surface ring-1 ring-stone-200 p-4 text-sm space-y-1.5">
        <div class="flex justify-between"><span class="text-stone-600">{{ __('shop.subtotal') }} (<span>{{ $qty }}</span> × <span>{{ $this->money($product->price) }}</span>)</span><b>{{ $this->money($this->subtotal()) }}</b></div>
        @if ($discount)<div class="flex justify-between text-emerald-700"><span>{{ __('shop.discount') }}</span><b>− {{ $this->money($discount) }}</b></div>@endif
        @if ($this->fee())<div class="flex justify-between"><span class="text-stone-600">{{ __('shop.processing_fee') }}</span><b>{{ $this->money($this->fee()) }}</b></div>@endif
        <div class="flex justify-between items-end pt-2 border-t border-dashed border-stone-300"><span class="font-semibold">{{ __('shop.total') }}</span><b class="font-display text-2xl text-brand-700">{{ $this->money($this->total()) }}</b></div>
      </div>

      @if ($error)<p class="p-3 rounded-xl bg-red-50 text-red-700 text-sm font-semibold">{{ $error }}</p>@endif

      <div class="grid grid-cols-[auto_1fr] gap-2">
        <button type="button" wire:click="addToCart" class="h-12 px-4 inline-flex items-center gap-2 rounded-xl ring-1 ring-brand-600 text-brand-700 font-semibold hover:bg-brand-50"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg> {{ __('shop.add_to_cart') }}</button>
        <button type="submit" class="h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/30 hover:from-brand-700 hover:to-brand-900"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> {{ __('shop.checkout') }} · <span>{{ $this->money($this->total()) }}</span></button>
      </div>
@php
    $showSettlement = \App\Support\Currencies::current() !== \App\Support\Currencies::BASE
        && (app(\App\Settings\CurrencySettings::class)->show_settlement_note ?? true);
@endphp
@if ($showSettlement)
      {{-- The customer is quoted in one currency and charged in another. Saying so before
           they commit is the difference between a conversion and a chargeback. --}}
      <p class="mt-3 text-xs text-center text-stone-500">{{ __('shop.settlement_note', ['display' => \App\Support\Currencies::current(), 'base' => \App\Support\Money::formatBase($this->total())]) }}</p>
@endif
      <p class="text-center text-xs text-stone-500">{{ __('shop.access_tracked_note') }}</p>
    </form>
  </div>
</div>
@endif
</div>
