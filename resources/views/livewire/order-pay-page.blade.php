  <div class="max-w-2xl mx-auto px-4">
    <div class="bg-white rounded-3xl shadow-2xl ring-1 ring-black/5 overflow-hidden">
      <div class="p-6 md:p-8 text-center">
        <span
          class="mx-auto grid place-items-center w-16 h-16 rounded-full {{ $this->isSettled() ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
          @if ($this->isSettled())
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
              aria-hidden="true">
              <path d="M20 6 9 17l-5-5" />
          </svg>@else<svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 6 12 12 16 14" />
            </svg>
          @endif
        </span>
        <h1 class="mt-4 font-display text-2xl md:text-3xl font-bold">{{ $this->headline() }}</h1>
        <p class="mt-2 text-stone-600">{{ $this->subheading() }}</p>
        <div class="mt-6 grid grid-cols-2 gap-3 text-left">
          <div class="p-4 rounded-2xl bg-surface ring-1 ring-stone-200">
            <p class="text-xs text-stone-500 uppercase tracking-wider font-semibold">{{ __('account.order_number') }}</p>
            <p class="mt-1 font-mono font-bold text-lg flex items-center gap-2"><span>{{ $order->order_number }}</span><button type="button"
                @click="window.copyText(@js($order->order_number))" class="text-stone-400 hover:text-brand-600" aria-label="{{ __('store.copy') }}"><svg
                  class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  aria-hidden="true">
                  <rect width="14" height="14" x="8" y="8" rx="2" />
                  <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                </svg></button></p>
          </div>
          <div class="p-4 rounded-2xl bg-brand-50 ring-1 ring-brand-200">
            <p class="text-xs text-brand-700 uppercase tracking-wider font-semibold">{{ __('account.amount') }}</p>
            <p class="mt-1 font-display font-bold text-2xl text-brand-700">{{ $this->money($order->total) }}</p>
          </div>
        </div>
      </div>

      @if ($method?->isManual() && !$this->isSettled())
        <div class="border-t border-stone-100 p-6 md:p-8 bg-amber-50/50">
          <div class="flex items-center gap-3"><span
              class="grid place-items-center w-11 h-11 rounded-xl text-white text-xs font-bold {{ $method->chip_color }}">{{ $method->t('short') }}</span>
            <div>
              <p class="font-semibold">Pay with {{ $method->t('name') }}</p>
              <p class="text-sm text-stone-600">{{ $method->account_type }} number</p>
            </div>
            <button type="button" @click="window.copyText(@js($method->account_number))"
              class="ml-auto inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-white ring-1 ring-amber-300 text-sm font-mono font-bold"><svg
                class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <rect width="14" height="14" x="8" y="8" rx="2" />
                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
              </svg><span>{{ $method->account_number }}</span></button>
          </div>
          <p class="mt-4 text-sm text-amber-900">{{ $method->t('instructions') }}</p>
          <form class="mt-4 grid sm:grid-cols-2 gap-3" wire:submit="submitProof">
            <input wire:model="sender" placeholder="{{ __('account.sender_number_placeholder') }}" maxlength="11"
              class="h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500">
            <input wire:model="trx" placeholder="{{ __('shop.transaction_id') }}"
              class="h-11 rounded-xl border-stone-200 uppercase placeholder:normal-case focus:border-brand-500 focus:ring-brand-500">
            @if ($error)
              <p class="sm:col-span-2 text-sm font-semibold text-red-600">{{ $error }}</p>
            @endif
            <button
              class="sm:col-span-2 h-12 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold">{{ __('account.submit_payment') }}</button>
          </form>
        </div>
      @elseif ($method?->isGateway() && !$this->isSettled())
        <div class="border-t border-stone-100 p-6 md:p-8">
          <form method="POST" action="{{ route('payments.shurjopay.start', ['type' => 'order', 'id' => $order->getKey()]) }}">@csrf<button type="submit"
              class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold"><svg
                class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <rect width="18" height="11" x="3" y="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg> {{ $lastPayment ? 'Try payment again' : 'Pay securely now' }}</button></form>
        </div>
      @endif

      {{-- Access-grant delivery: what has actually been given, per purchased item. --}}
      @if ($order->isPaid())
        <div class="border-t border-stone-100 p-6 md:p-8 space-y-4">
          @foreach ($order->items as $item)
            <div class="p-4 rounded-2xl bg-surface ring-1 ring-stone-200" wire:key="access-{{ $item->id }}">
              <div class="flex items-start gap-3">
                <span
                  class="grid place-items-center w-11 h-11 rounded-xl text-white shrink-0 bg-gradient-to-br {{ $item->product_snapshot['gradient'] ?? 'from-rose-500 to-red-700' }}"><x-store.icon
                    :name="$item->product_snapshot['icon_key'] ?? 'play'" class="w-5 h-5" /></span>
                <div class="flex-1 min-w-0">
                  <b class="block text-sm">{{ $item->name() }}</b>
                  <p class="text-xs text-stone-600">{{ __('account.access_email') }} <b class="text-ink">{{ $item->delivery_email }}</b></p>
                  @if ($item->access_status->isLive())
                    <p class="mt-2 text-sm text-stone-700">{{ $item->inviteInstructions() ?: 'Check your inbox for the invitation and accept it.' }}</p>
                    @if (filled($item->admin_delivery_note))
                      <p class="mt-1 text-sm text-stone-700">{{ strip_tags($item->admin_delivery_note) }}</p>
                    @endif
                  @else
                    <p class="mt-2 text-sm text-stone-600">We are granting access now — usually within {{ $item->slaMinutes() }} minutes.</p>
                  @endif
                </div>
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold shrink-0 {{ $item->access_status->isLive() ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' }}"><span
                    class="w-1.5 h-1.5 rounded-full {{ $item->access_status->isLive() ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>{{ $item->access_status->label() }}</span>
              </div>
              @if ($item->access_status->isLive() && $item->access_status !== \App\Enums\AccessStatus::Confirmed)
                <div class="mt-3 grid sm:grid-cols-2 gap-2">
                  <a href="{{ URL::signedRoute('access.confirm', ['item' => $item->id]) }}"
                    class="h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-ink text-white text-sm font-semibold">{{ __('account.i_received_access') }}</a>
                  <a href="{{ URL::signedRoute('access.problem', ['item' => $item->id]) }}"
                    class="h-11 inline-flex items-center justify-center rounded-xl ring-1 ring-stone-200 text-sm font-semibold hover:bg-stone-50">{{ __('account.i_didnt_get_the_invite') }}</a>
                </div>
              @endif
            </div>
          @endforeach
        </div>
      @endif

      <div class="border-t border-stone-100 p-5 flex flex-col sm:flex-row gap-3">
        <a href="{{ url('/customer/orders') }}"
          class="flex-1 h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-ink text-white font-semibold"><svg class="w-4 h-4"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="7" height="7" x="3" y="3" rx="1" />
            <rect width="7" height="7" x="14" y="3" rx="1" />
            <rect width="7" height="7" x="14" y="14" rx="1" />
            <rect width="7" height="7" x="3" y="14" rx="1" />
          </svg> {{ __('account.go_to_my_account') }}</a>
        <a href="{{ route('home') }}"
          class="flex-1 h-11 inline-flex items-center justify-center rounded-xl ring-1 ring-stone-200 font-semibold hover:bg-stone-50">{{ __('store.continue_shopping') }}</a>
      </div>
    </div>
    <p class="mt-6 text-center text-sm text-stone-600">{{ __('account.problem_with_payment') }} <a href="{{ route('contact') }}"
        class="font-semibold text-brand-700">{{ __('store.contact_support') }}</a> {{ __('account.with_your_order_number') }}</p>
  </div>
