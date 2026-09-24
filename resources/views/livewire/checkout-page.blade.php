  @php
    // Only the lines that actually ask something get a step of their own.
    $needsFields = $items->filter(fn($item) => $item->product?->fields->isNotEmpty());
    $step = 1;
  @endphp

  <form class="wrap grid lg:grid-cols-[1fr_400px] gap-6 items-start" wire:submit="place" novalidate>
    <div class="space-y-6" data-track="initiate_checkout" data-track-payload='@json($trackPayload)'>

      {{-- Where the access is going. Everything this shop sells is granted to an email
           address, so this is the one field an order cannot be fulfilled without. --}}
      <section class="panel panel-p">
        <h2 class="h-panel flex items-center gap-3">
          <span class="tnum grid place-items-center w-7 h-7 rounded-full bg-navy-900 text-white text-[13px] font-semibold">{{ $step++ }}</span>
          {{ __('shop.delivery_details') }}
        </h2>

        <div class="mt-5 grid sm:grid-cols-2 gap-4">
          <label class="block sm:col-span-2">
            <span class="field-label">{{ __('shop.delivery_email') }} <span class="field-req">*</span></span>
            <span class="relative block">
              <x-store.ui name="mail" class="field-affix w-4 h-4" />
              <input type="email" wire:model.blur="email" class="field field-icon" placeholder="you@example.com">
            </span>
            <span class="field-hint">{{ $deliveryHelp }}</span>
          </label>
        </div>
      </section>

      @if ($needsFields->isNotEmpty())
        {{-- What each product needs before anybody can grant it: a page ID, a region, a
             username. Asked here rather than in a pop-up on the way past, because these
             answers are part of the order and the customer may still edit the cart. --}}
        <section class="panel panel-p">
          <h2 class="h-panel flex items-center gap-3">
            <span class="tnum grid place-items-center w-7 h-7 rounded-full bg-navy-900 text-white text-[13px] font-semibold">{{ $step++ }}</span>
            {{ __('shop.what_we_need') }}
          </h2>

          <div class="mt-5 space-y-5">
            @foreach ($needsFields as $item)
              <div wire:key="fields-{{ $item->id }}">
                <p class="flex items-center gap-2.5 text-sm font-semibold text-navy-900">
                  <span class="well w-8 h-8 rounded-md border border-navy-100 shrink-0">
                    @if ($item->product->image)
                      <img src="{{ Storage::disk('public')->url($item->product->image) }}" alt="" loading="lazy"
                        class="absolute inset-0 w-full h-full object-fill">
                    @else
                      <x-store.icon :name="$item->product->icon_key" class="w-4 h-4 text-navy-400" />
                    @endif
                  </span>{{ $item->product->t('name') }}
                </p>

                <div class="mt-3 grid sm:grid-cols-2 gap-4">
                  @foreach ($item->product->fields as $field)
                    <label class="block {{ $field->type === \App\Enums\FieldType::Textarea ? 'sm:col-span-2' : '' }}">
                      <span class="field-label">{{ $field->t('label') }}
                        @if ($field->required)
                          <span class="field-req">*</span>
                        @endif
                      </span>
                      @if ($field->type === \App\Enums\FieldType::Select)
                        <select wire:model="lineFields.{{ $item->id }}.{{ $field->key }}" class="field">
                          @foreach ($field->options ?? [] as $option)
                            <option>{{ $option }}</option>
                          @endforeach
                        </select>
                      @elseif ($field->type === \App\Enums\FieldType::Textarea)
                        <textarea wire:model="lineFields.{{ $item->id }}.{{ $field->key }}" rows="2"
                          placeholder="{{ $field->t('placeholder') }}" class="field"></textarea>
                      @else
                        <input type="{{ $field->type->value }}" wire:model="lineFields.{{ $item->id }}.{{ $field->key }}"
                          placeholder="{{ $field->t('placeholder') }}" class="field">
                      @endif
                    </label>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </section>
      @endif

      {{-- How it gets paid for. The route an order takes is recorded on the order, so it is
           named here in the same words it will carry afterwards. --}}
      <section class="panel panel-p">
        <h2 class="h-panel flex items-center gap-3">
          <span class="tnum grid place-items-center w-7 h-7 rounded-full bg-navy-900 text-white text-[13px] font-semibold">{{ $step++ }}</span>
          {{ __('shop.payment_method') }}
        </h2>

        <div class="mt-5 grid sm:grid-cols-2 gap-3">
          @foreach ($methods as $option)
            @php $walletBlocked = $option->isWallet() && $this->walletDisabled(); @endphp
            <label
              class="relative flex items-center gap-3 p-4 rounded-[var(--radius-panel)] border cursor-pointer transition
                {{ $paymentKey === $option->key ? 'border-brand-600 bg-brand-50/60 ring-1 ring-brand-600' : 'border-navy-200 hover:border-navy-400' }}
                {{ $walletBlocked ? 'opacity-55 cursor-not-allowed' : '' }}">
              <input type="radio" name="payment" class="sr-only" value="{{ $option->key }}" wire:model.live="paymentKey" @disabled($walletBlocked)>
              <span
                class="grid place-items-center w-11 h-11 rounded-[var(--radius-control)] text-white text-xs font-bold shrink-0 {{ $option->chip_color }}">{{ $option->short }}</span>
              <span class="flex-1 min-w-0">
                <b class="block text-sm text-navy-900">{{ $option->name }}</b>
                @if ($option->isWallet())
                  <span class="text-xs text-navy-500">
                    @auth
                      {{ __('shop.balance') }} <b class="tnum text-navy-900">{{ $this->money((int) auth()->user()->balance) }}</b>
                      @if ($this->walletDisabled())
                        <b class="text-brand-700">· {{ __('shop.insufficient') }}</b>
                      @endif
                    @endauth
                    @guest {{ __('shop.sign_in_to_use_wallet') }} @endguest
                  </span>
                @elseif ($option->isManual())
                  <span class="text-xs text-navy-500">{{ __('shop.send_money_manual') }}</span>
                @elseif ($option->isGateway())
                  <span class="text-xs text-navy-500">{{ __('shop.card_methods') }}</span>
                @endif
              </span>
              <span
                class="grid place-items-center w-5 h-5 rounded-full border-2 shrink-0 transition {{ $paymentKey === $option->key ? 'border-brand-600 bg-brand-600' : 'border-navy-300' }}">
                @if ($paymentKey === $option->key)
                  <x-store.ui name="check" class="w-3 h-3 text-white" stroke-width="3" />
                @endif
              </span>
            </label>
          @endforeach
        </div>

        @php $method = $this->method(); @endphp

        @if ($method?->isManual())
          {{-- A manual transfer is a promise until somebody checks it, so the page collects
               what the check needs: the number it came from, the reference, the receipt. --}}
          <div class="mt-5 rounded-[var(--radius-panel)] border border-hold-100 bg-hold-50/70 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
              <div>
                <p class="flex items-center gap-2 text-[13px] font-semibold text-hold-700">
                  <x-store.ui name="wallet" class="w-4 h-4" />{{ $method->t('name') }} · {{ $method->account_type }}
                </p>
                <p class="tnum mt-1.5 font-display text-2xl font-bold tracking-wide text-navy-900">{{ $method->account_number }}</p>
              </div>
              <button type="button" @click="window.copyText(@js($method->account_number))" class="btn btn-quiet">
                <x-store.ui name="copy" class="w-4 h-4" />{{ __('shop.copy_number') }}</button>
            </div>

            <ol class="mt-4 space-y-1.5 text-sm text-hold-900 list-decimal list-inside">
              <li>{{ $method->t('instructions') }}</li>
              <li>{{ __('shop.pay_exactly') }} <b class="tnum">{{ $this->money($this->total()) }}</b> {{ __('shop.and_fill_fields') }}</li>
            </ol>

            <div class="mt-4 grid sm:grid-cols-2 gap-4">
              <label class="block"><span class="field-label">{{ __('shop.sender_number') }} <span class="field-req">*</span></span>
                <input wire:model="sender" inputmode="numeric" maxlength="11" placeholder="01XXXXXXXXX" class="field"></label>
              <label class="block"><span class="field-label">{{ __('shop.transaction_id') }} <span class="field-req">*</span></span>
                <input wire:model="trx" placeholder="9FJ3K2LQ1A" class="field field-code"></label>
              <label
                class="sm:col-span-2 flex items-center gap-4 p-4 rounded-[var(--radius-panel)] border-2 border-dashed border-hold-100 bg-white cursor-pointer hover:border-brand-400 transition">
                <span class="grid place-items-center w-10 h-10 rounded-[var(--radius-control)] bg-navy-50 text-navy-500 shrink-0">
                  <x-store.ui name="upload" class="w-5 h-5" /></span>
                <span class="flex-1 text-sm min-w-0">
                  <b class="block text-navy-900 truncate">{{ $screenshot?->getClientOriginalName() ?: __('shop.upload_screenshot') }}</b>
                  <span class="text-navy-500">{{ __('shop.screenshot_hint') }}</span></span>
                <span wire:loading wire:target="screenshot" class="text-xs text-navy-500">{{ __('store.uploading') }}</span>
                <input type="file" accept="image/*" class="sr-only" wire:model="screenshot"></label>
            </div>
          </div>
        @elseif ($method)
          <p class="note note-ok mt-5 flex items-start gap-2"><x-store.ui name="info" class="w-4 h-4 mt-0.5 shrink-0" />{{ $method->t('instructions') }}
          </p>
          @if ($method->isGateway())
            <label class="block mt-4"><span class="field-label">{{ __('shop.phone_number') }} <span class="field-req">*</span></span>
              <span class="relative block"><x-store.ui name="phone" class="field-affix w-4 h-4" />
                <input type="tel" wire:model.blur="customerPhone" placeholder="01XXXXXXXXX" class="field field-icon"></span></label>
          @endif
        @endif
      </section>
    </div>

    {{-- The order, priced once, on the server. --}}
    <aside class="panel panel-p lg:sticky lg:top-6">
      <h2 class="h-panel">{{ __('shop.your_order') }}</h2>

      <ul class="mt-4 divide-y divide-navy-100">
        @forelse ($items as $item)
          @continue(!$item->product)
          <li class="py-3 flex gap-3 text-sm" wire:key="sum-{{ $item->id }}">
            <span class="well w-11 h-11 rounded-[var(--radius-control)] border border-navy-100 shrink-0">
              @if ($item->product->image)
                <img src="{{ Storage::disk('public')->url($item->product->image) }}" alt="" loading="lazy"
                  class="absolute inset-0 w-full h-full object-fill">
              @else
                <x-store.icon :name="$item->product->icon_key" class="w-5 h-5 text-navy-400" />
              @endif
            </span>
            <span class="flex-1 min-w-0"><b class="block truncate text-navy-900">{{ $item->product->name }}</b>
              <span class="tnum text-navy-500">{{ __('shop.quantity') }} {{ $item->quantity }}</span></span>
            <b class="tnum text-navy-900">{{ $this->money($item->lineTotal()) }}</b>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-navy-500">{{ __('shop.cart_is_empty_add') }}
            <a href="{{ route('home') }}" class="link">{{ __('shop.add_products') }}</a>
          </li>
        @endforelse
      </ul>

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
          <dd class="tnum">{{ $this->money($this->subtotal()) }}</dd>
        </div>
        @if ($couponOk)
          <div class="kv">
            <dt class="text-seat-700">{{ __('shop.discount') }}</dt>
            <dd class="tnum text-seat-700">− {{ $this->money($discount) }}</dd>
          </div>
        @endif
        @if ($this->fee())
          <div class="kv">
            <dt>{{ __('shop.processing_fee') }}</dt>
            <dd class="tnum">{{ $this->money($this->fee()) }}</dd>
          </div>
        @endif
      </dl>

      <div class="mt-3 pt-4 rule-dashed flex items-end justify-between">
        <span class="font-semibold text-navy-900">{{ __('shop.total') }}</span>
        <span class="tnum font-display text-2xl font-bold text-navy-900">{{ $this->money($this->total()) }}</span>
      </div>

      @if ($error)
        <p class="note note-bad mt-4 flex items-start gap-2 font-semibold"><x-store.ui name="alert" class="w-4 h-4 mt-0.5 shrink-0" />{{ $error }}
        </p>
      @endif

      <button type="submit" class="btn btn-primary btn-lg btn-block sheen mt-5" wire:loading.attr="disabled">
        <x-store.ui name="lock" class="w-4 h-4" wire:loading.remove wire:target="place" />
        <x-store.ui name="refresh" class="w-4 h-4 spin-slow" wire:loading wire:target="place" />
        {{ __('shop.place_order') }} · <span class="tnum">{{ $this->money($this->total()) }}</span></button>

      @php
        $showSettlement =
            \App\Support\Currencies::current() !== \App\Support\Currencies::BASE && (app(\App\Settings\CurrencySettings::class)->show_settlement_note ?? true);
      @endphp
      @if ($showSettlement)
        {{-- The customer is quoted in one currency and charged in another. Saying so before
             they commit is the difference between a conversion and a chargeback. --}}
        <p class="mt-3 text-xs text-center text-navy-500">
          {{ __('shop.settlement_note', ['display' => \App\Support\Currencies::current(), 'base' => \App\Support\Money::formatBase($this->total())]) }}</p>
      @endif

      <p class="mt-3 text-xs text-center text-navy-500">{{ __('shop.agree_prefix') }}
        <a href="{{ url('/page/terms') }}" class="underline hover:text-navy-700">{{ __('shop.terms') }}</a> &amp;
        <a href="{{ url('/page/refund-policy') }}" class="underline hover:text-navy-700">{{ __('shop.refund_policy') }}</a>.
      </p>
    </aside>
  </form>
