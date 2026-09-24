  <div class="max-w-2xl mx-auto px-4">
    <div class="panel overflow-hidden">
      {{-- Where the order stands, before anything else. --}}
      <div class="p-6 md:p-8 text-center">
        <span
          class="mx-auto grid place-items-center w-16 h-16 rounded-full {{ $this->isSettled() ? 'bg-seat-50 text-seat-700' : 'bg-hold-50 text-hold-700' }}">
          <x-store.ui :name="$this->isSettled() ? 'check' : 'clock'" class="w-8 h-8" :stroke-width="$this->isSettled() ? 2.5 : 2" />
        </span>

        <h1 class="mt-4 font-display text-2xl md:text-3xl font-bold tracking-[-0.02em] text-navy-900">{{ $this->headline() }}</h1>
        <p class="mt-2 text-navy-500">{{ $this->subheading() }}</p>

        <div class="mt-6 grid grid-cols-2 gap-3 text-left">
          <div class="panel p-4 bg-navy-50/60">
            <p class="flex items-center gap-1.5 text-xs text-navy-500"><x-store.ui name="receipt"
                class="w-3.5 h-3.5" />{{ __('account.order_number') }}</p>
            <p class="tnum mt-1.5 font-display font-bold text-lg text-navy-900 flex items-center gap-2">
              <span>{{ $order->order_number }}</span>
              <button type="button" @click="window.copyText(@js($order->order_number))" class="text-navy-400 hover:text-brand-600 transition"
                aria-label="{{ __('store.copy') }}"><x-store.ui name="copy" class="w-4 h-4" /></button>
            </p>
          </div>
          <div class="panel p-4 bg-brand-50/60 border-brand-100">
            <p class="flex items-center gap-1.5 text-xs text-brand-700"><x-store.ui name="card" class="w-3.5 h-3.5" />{{ __('account.amount') }}</p>
            <p class="tnum mt-1.5 font-display font-bold text-2xl text-brand-700">{{ $this->money($order->total) }}</p>
          </div>
        </div>
      </div>

      @if ($method?->isManual() && !$this->isSettled())
        {{-- Manual transfer: the number to send to, and the reference somebody will check
             the transfer against. --}}
        <div class="border-t border-navy-100 p-6 md:p-8 bg-hold-50/50">
          <div class="flex flex-wrap items-center gap-3">
            <span
              class="grid place-items-center w-11 h-11 rounded-[var(--radius-control)] text-white text-xs font-bold {{ $method->chip_color }}">{{ $method->t('short') }}</span>
            <div class="min-w-0">
              <p class="font-semibold text-navy-900">{{ __('shop.pay_with', ['method' => $method->t('name')]) }}</p>
              <p class="text-sm text-navy-500">{{ $method->account_type }}</p>
            </div>
            <button type="button" @click="window.copyText(@js($method->account_number))" class="btn btn-quiet ml-auto">
              <x-store.ui name="copy" class="w-4 h-4" /><span class="tnum">{{ $method->account_number }}</span></button>
          </div>

          <p class="mt-4 text-sm text-hold-900">{{ $method->t('instructions') }}</p>

          <form class="mt-4 grid sm:grid-cols-2 gap-3" wire:submit="submitProof">
            <input wire:model="sender" placeholder="{{ __('account.sender_number_placeholder') }}" maxlength="11" class="field">
            <input wire:model="trx" placeholder="{{ __('shop.transaction_id') }}" class="field field-code">
            @if ($error)
              <p class="sm:col-span-2 flex items-center gap-1.5 text-sm font-semibold text-brand-700">
                <x-store.ui name="alert" class="w-4 h-4" />{{ $error }}
              </p>
            @endif
            <button class="btn btn-primary btn-lg sm:col-span-2 sheen">{{ __('account.submit_payment') }}</button>
          </form>
        </div>
      @elseif ($method?->isGateway() && !$this->isSettled())
        <div class="border-t border-navy-100 p-6 md:p-8">
          <form method="POST" action="{{ route('payments.shurjopay.start', ['type' => 'order', 'id' => $order->getKey()]) }}">@csrf
            <button type="submit" class="btn btn-primary btn-lg btn-block sheen"><x-store.ui name="lock" class="w-4 h-4" />
              {{ $lastPayment ? __('account.try_payment_again') : __('account.pay_securely_now') }}</button>
          </form>
        </div>
      @endif

      {{-- Access-grant delivery: what has actually been given, per purchased item. --}}
      @if ($order->isPaid())
        <div class="border-t border-navy-100 p-6 md:p-8 space-y-4">
          @foreach ($order->items as $item)
            <div class="panel p-4 bg-navy-50/50" wire:key="access-{{ $item->id }}">
              <div class="flex items-start gap-3">
                <span class="grid place-items-center w-11 h-11 rounded-[var(--radius-control)] bg-navy-900 text-white shrink-0">
                  <x-store.icon :name="$item->product_snapshot['icon_key'] ?? 'play'" class="w-5 h-5" /></span>

                <div class="flex-1 min-w-0">
                  <b class="block text-sm text-navy-900">{{ $item->name() }}</b>
                  <p class="text-xs text-navy-500">{{ __('account.access_email') }} <b class="text-navy-900">{{ $item->delivery_email }}</b></p>

                  @if ($item->access_status->isLive())
                    <p class="mt-2 text-sm text-navy-700">{{ $item->inviteInstructions() ?: __('shop.step_accept_hint') }}</p>
                    @if (filled($item->admin_delivery_note))
                      <p class="mt-1 text-sm text-navy-700">{{ strip_tags($item->admin_delivery_note) }}</p>
                    @endif
                  @else
                    <p class="mt-2 text-sm text-navy-600">{{ __('account.granting_now', ['count' => $item->slaMinutes()]) }}</p>
                  @endif
                </div>

                <span class="flag shrink-0 {{ $item->access_status->isLive() ? 'flag-live' : 'flag-hold' }}">
                  @if ($item->access_status->isLive())
                    <span class="live-dot"></span>
                  @else
                    <span class="flag-dot"></span>
                  @endif
                  {{ $item->access_status->label() }}
                </span>
              </div>

              @if ($item->access_status->isLive() && $item->access_status !== \App\Enums\AccessStatus::Confirmed)
                <div class="mt-3 grid sm:grid-cols-2 gap-2">
                  <a href="{{ URL::signedRoute('access.confirm', ['item' => $item->id]) }}" class="btn btn-dark">
                    <x-store.ui name="check" class="w-4 h-4" />{{ __('account.i_received_access') }}</a>
                  <a href="{{ URL::signedRoute('access.problem', ['item' => $item->id]) }}" class="btn btn-quiet">
                    {{ __('account.i_didnt_get_the_invite') }}</a>
                </div>
              @endif
            </div>
          @endforeach
        </div>
      @endif

      <div class="border-t border-navy-100 p-5 flex flex-col sm:flex-row gap-3">
        <a href="{{ url('/customer/orders') }}" class="btn btn-dark flex-1"><x-store.ui name="grid"
            class="w-4 h-4" />{{ __('account.go_to_my_account') }}</a>
        <a href="{{ route('home') }}" class="btn btn-quiet flex-1">{{ __('store.continue_shopping') }}</a>
      </div>
    </div>

    <p class="mt-6 text-center text-sm text-navy-500">{{ __('account.problem_with_payment') }}
      <a href="{{ route('contact') }}" class="link">{{ __('store.contact_support') }}</a> {{ __('account.with_your_order_number') }}
    </p>
  </div>
