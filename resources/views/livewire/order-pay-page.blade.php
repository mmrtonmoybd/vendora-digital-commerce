{{--
  The receipt for an issued pass.

  Whatever brought the customer here — a fresh order, a link out of the delivery email, a
  payment they need to finish — what they want off this page is the same four things: where
  the order stands, the reference both sides quote, what it costs, and what has actually
  been granted. So it is set as a ticket rather than as a status card: an ink head carrying
  the state, a perforation, and the terms ruled below it.
--}}
<div class="wrap-narrow">
  <article class="reveal panel overflow-hidden">

    {{-- The state, on ink. It is the one thing somebody returning to this page is here to
         read, so it gets the dark ground and nothing else competes with it. --}}
    <div class="slab guilloche rounded-b-none p-6 md:p-8">
      <div class="relative flex items-start gap-4">
        <span
          class="grid place-items-center w-12 h-12 rounded-full shrink-0 {{ $this->isSettled() ? 'bg-grant-50 text-grant-700' : 'bg-hold-50 text-hold-700' }}">
          <x-store.ui :name="$this->isSettled() ? 'check' : 'clock'" class="w-6 h-6" :stroke-width="$this->isSettled() ? 2.5 : 2" />
        </span>

        <div class="min-w-0">
          <span class="flag {{ $this->isSettled() ? 'flag-live' : 'flag-hold' }}">
            <span class="flag-dot"></span>{{ $order->payment_status->label() }}
          </span>
          {{-- An h2, not an h1: the page masthead above already names the page. --}}
          <h2 class="mt-3 h-section text-white">{{ $this->headline() }}</h2>
          <p class="mt-2 text-[15px] leading-relaxed text-white/65">{{ $this->subheading() }}</p>
        </div>
      </div>
    </div>

    {{-- The stub: the facts a customer quotes back at us, ruled the way a docket rules
         them. The amount carries the accent because on an unpaid order it is the figure
         the next action is about. --}}
    <div class="tear px-6 md:px-8 py-4">
      <dl>
        <div class="kv">
          <dt>{{ __('account.order_number') }}</dt>
          <dd class="tnum flex items-center gap-2">{{ $order->order_number }}
            <button type="button" @click="window.copyText(@js($order->order_number))"
              class="tap text-ink-400 hover:text-brand-600 transition" aria-label="{{ __('store.copy') }}">
              <x-store.ui name="copy" class="w-4 h-4" /></button>
          </dd>
        </div>
        @if ($method)
          <div class="kv">
            <dt>{{ __('invoice.payment') }}</dt>
            <dd>{{ $method->t('name') }}</dd>
          </div>
        @endif
        <div class="kv">
          <dt>{{ __('account.amount') }}</dt>
          <dd class="tnum text-[19px] text-brand-700">{{ $this->money($order->total) }}</dd>
        </div>
      </dl>
    </div>

    @if ($method?->isManual() && !$this->isSettled())
      {{-- The payment docket: the number to send to, and the reference a person will check
           the transfer against before anything is granted. It sits on clay ground because
           everything in it is waiting on a human. --}}
      <div class="tear px-6 md:px-8 py-6 bg-hold-50/40">
        <p class="label-line">{{ __('shop.pay_with', ['method' => $method->t('name')]) }}</p>

        <div class="mt-4 flex flex-wrap items-center gap-3">
          <span
            class="grid place-items-center w-11 h-11 rounded-[var(--radius-control)] text-white text-xs font-bold {{ $method->chip_color }}">{{ $method->t('short') }}</span>
          <div class="min-w-0">
            <p class="font-display font-bold text-ink-900">{{ $method->t('name') }}</p>
            <p class="text-sm text-ink-500">{{ $method->account_type }}</p>
          </div>
          <button type="button" @click="window.copyText(@js($method->account_number))" class="btn btn-quiet ml-auto">
            <x-store.ui name="copy" class="w-4 h-4" /><span class="tnum">{{ $method->account_number }}</span></button>
        </div>

        <p class="note note-hold mt-4">{{ $method->t('instructions') }}</p>

        <form class="mt-5 grid sm:grid-cols-2 gap-4" wire:submit="submitProof">
          <label class="block">
            <span class="field-label">{{ __('account.sender_number') }} <span class="field-req">*</span></span>
            <input wire:model="sender" placeholder="{{ __('account.sender_number_placeholder') }}" maxlength="11" class="field tnum">
          </label>

          <label class="block">
            <span class="field-label">{{ __('shop.transaction_id') }} <span class="field-req">*</span></span>
            <input wire:model="trx" placeholder="{{ __('shop.transaction_id') }}" class="field field-code">
          </label>

          @if ($error)
            <p class="note note-bad sm:col-span-2 flex items-start gap-2 font-semibold">
              <x-store.ui name="alert" class="w-4 h-4 mt-0.5 shrink-0" />{{ $error }}
            </p>
          @endif

          <button class="group btn btn-primary btn-lg sm:col-span-2 sheen">{{ __('account.submit_payment') }}
            <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></button>
        </form>
      </div>
    @elseif ($method?->isGateway() && !$this->isSettled())
      <div class="tear px-6 md:px-8 py-6">
        <form method="POST" action="{{ route('payments.shurjopay.start', ['type' => 'order', 'id' => $order->getKey()]) }}">@csrf
          <button type="submit" class="btn btn-primary btn-lg btn-block sheen"><x-store.ui name="lock" class="w-4 h-4" />
            {{ $lastPayment ? __('account.try_payment_again') : __('account.pay_securely_now') }}</button>
        </form>
      </div>
    @endif

    {{-- One docket per grant. A purchased line is not a shipped item: it is permission that
         either exists on the platform now or is still being arranged, so each carries its
         own state and its own next step. --}}
    @if ($order->isPaid())
      <div class="tear px-6 md:px-8 py-6">
        <p class="label-line">{{ __('account.what_was_granted') }}</p>

        <div class="stagger mt-4 space-y-3">
          @foreach ($order->items as $item)
            <div class="reveal pass pass-flat p-4" style="--i: {{ $loop->index }}" wire:key="access-{{ $item->id }}">
              <div class="flex items-start gap-3">
                <span class="grid place-items-center w-11 h-11 rounded-[var(--radius-control)] bg-ink-900 text-white shrink-0">
                  <x-store.icon :name="$item->product_snapshot['icon_key'] ?? 'play'" class="w-5 h-5" /></span>

                <div class="flex-1 min-w-0">
                  <b class="block font-display text-sm text-ink-900">{{ $item->name() }}</b>

                  <dl>
                    <div class="kv py-1">
                      <dt>{{ __('account.access_email') }}</dt>
                      <dd class="shrink break-all">{{ $item->delivery_email }}</dd>
                    </div>
                  </dl>

                  @if ($item->access_status->isLive())
                    <p class="mt-1 text-sm text-ink-700">{{ $item->inviteInstructions() ?: __('shop.step_accept_hint') }}</p>
                    @if (filled($item->admin_delivery_note))
                      <p class="mt-1 text-sm text-ink-700">{{ strip_tags($item->admin_delivery_note) }}</p>
                    @endif
                  @else
                    <p class="mt-1 text-sm text-ink-600">{{ __('account.granting_now', ['count' => $item->slaMinutes()]) }}</p>
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

              {{-- Only a live grant can be confirmed or disputed, and a grant the customer
                   has already confirmed needs neither. --}}
              @if ($item->access_status->isLive() && $item->access_status !== \App\Enums\AccessStatus::Confirmed)
                <div class="tear mt-4 pt-4 grid sm:grid-cols-2 gap-2">
                  <a href="{{ URL::signedRoute('access.confirm', ['item' => $item->id]) }}" class="btn btn-dark">
                    <x-store.ui name="check" class="w-4 h-4" />{{ __('account.i_received_access') }}</a>
                  <a href="{{ URL::signedRoute('access.problem', ['item' => $item->id]) }}" class="btn btn-quiet">
                    {{ __('account.i_didnt_get_the_invite') }}</a>
                </div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <div class="tear px-6 md:px-8 py-5 flex flex-col sm:flex-row gap-3">
      <a href="{{ url('/customer/orders') }}" class="group btn btn-dark flex-1"><x-store.ui name="grid"
          class="w-4 h-4" />{{ __('account.go_to_my_account') }}</a>
      <a href="{{ route('home') }}" class="group btn btn-quiet flex-1">{{ __('store.continue_shopping') }}
        <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
    </div>
  </article>

  <p class="mt-6 text-center text-sm text-ink-500">{{ __('account.problem_with_payment') }}
    <a href="{{ route('contact') }}" class="link">{{ __('store.contact_support') }}</a> {{ __('account.with_your_order_number') }}
  </p>
</div>
