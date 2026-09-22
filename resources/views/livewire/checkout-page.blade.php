  <form class="max-w-7xl mx-auto px-4 grid lg:grid-cols-[1fr_400px] gap-6 items-start" wire:submit="place" novalidate>
    <div class="space-y-6" data-track="initiate_checkout" data-track-payload='@json($trackPayload)'>
      <!-- step 1 -->
      <section class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-5 md:p-6">
        <h2 class="flex items-center gap-3 font-display font-semibold text-lg"><span class="grid place-items-center w-8 h-8 rounded-full bg-brand-600 text-white text-sm">1</span> Delivery details</h2>
        <div class="mt-4 grid sm:grid-cols-2 gap-4">
          <label class="block sm:col-span-2"><span class="text-sm font-semibold">Delivery email <span class="text-brand-600">*</span></span>
            <input type="email" wire:model.blur="email" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500" placeholder="you@example.com">
            <span class="mt-1 block text-xs text-stone-500">{{ $deliveryHelp }}</span></label>
          @guest<p class="sm:col-span-2 text-sm text-stone-600">Have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Sign in</a> to pay with wallet and track orders.</p>@endguest
        </div>
      </section>

      <!-- step 2 -->
      <section class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-5 md:p-6">
        <h2 class="flex items-center gap-3 font-display font-semibold text-lg"><span class="grid place-items-center w-8 h-8 rounded-full bg-brand-600 text-white text-sm">2</span> Payment method</h2>
        <div class="mt-4 grid sm:grid-cols-2 gap-3">
          @foreach ($methods as $option)
            @php $walletBlocked = $option->isWallet() && $this->walletDisabled(); @endphp
            <label class="relative flex items-center gap-3 p-4 rounded-2xl ring-1 cursor-pointer transition {{ $paymentKey === $option->key ? 'ring-2 ring-brand-600 bg-brand-50' : 'ring-stone-200 hover:ring-stone-300' }} {{ $walletBlocked ? 'opacity-55 cursor-not-allowed' : '' }}">
              <input type="radio" name="payment" class="sr-only" value="{{ $option->key }}" wire:model.live="paymentKey" @disabled($walletBlocked)>
              <span class="grid place-items-center w-11 h-11 rounded-xl text-white text-xs font-bold {{ $option->chip_color }}">{{ $option->short }}</span>
              <span class="flex-1"><b class="block text-sm">{{ $option->name }}</b>
                @if ($option->isWallet())<span class="text-xs text-stone-500">@auth Balance: <b class="text-ink">{{ $this->money((int) auth()->user()->balance) }}</b>@if ($this->walletDisabled())<span class="text-red-600 font-semibold"> · insufficient</span>@endif @endauth @guest Sign in to use wallet @endguest</span>@endif
                @if ($option->isManual())<span class="text-xs text-stone-500">Send money · manual verify</span>@endif
                @if ($option->isGateway())<span class="text-xs text-stone-500">Visa, Mastercard, net banking</span>@endif</span>
              <span class="grid place-items-center w-5 h-5 rounded-full ring-2 {{ $paymentKey === $option->key ? 'ring-brand-600 bg-brand-600' : 'ring-stone-300' }}">@if ($paymentKey === $option->key)<span class="w-2 h-2 rounded-full bg-white"></span>@endif</span>
            </label>
          @endforeach
        </div>

        @php $method = $this->method(); @endphp
        @if ($method?->isManual())
          <div class="mt-5 rounded-2xl ring-1 ring-amber-200 bg-amber-50/60 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
              <div><p class="text-xs font-bold uppercase tracking-wider text-amber-800">{{ $method->name }} · {{ $method->account_type }}</p>
                <p class="mt-1 font-mono text-2xl font-bold tracking-wider">{{ $method->account_number }}</p></div>
              <button type="button" @click="window.copyText(@js($method->account_number))" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-white ring-1 ring-amber-300 text-sm font-semibold hover:bg-amber-100"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg> Copy number</button>
            </div>
            <ol class="mt-4 text-sm text-amber-900 space-y-1 list-decimal list-inside"><li>{{ $method->instructions }}</li><li>Pay exactly <b>{{ $this->money($this->total()) }}</b> and fill the fields below.</li></ol>
            <div class="mt-4 grid sm:grid-cols-2 gap-4">
              <label class="block"><span class="text-sm font-semibold">Sender number <span class="text-brand-600">*</span></span><input wire:model="sender" inputmode="numeric" maxlength="11" placeholder="01XXXXXXXXX" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 bg-white focus:border-brand-500 focus:ring-brand-500"></label>
              <label class="block"><span class="text-sm font-semibold">Transaction ID <span class="text-brand-600">*</span></span><input wire:model="trx" placeholder="e.g. 9FJ3K2LQ1A" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 bg-white uppercase placeholder:normal-case focus:border-brand-500 focus:ring-brand-500"></label>
              <label class="sm:col-span-2 flex items-center gap-4 p-4 rounded-xl border-2 border-dashed border-amber-300 bg-white cursor-pointer hover:border-brand-400">
                <span class="grid place-items-center w-10 h-10 rounded-lg bg-brand-50 text-brand-600"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg></span>
                <span class="flex-1 text-sm"><b class="block">{{ $screenshot?->getClientOriginalName() ?: 'Upload payment screenshot' }}</b><span class="text-stone-500">PNG or JPG, up to 4 MB (optional)</span></span>
                <input type="file" accept="image/*" class="sr-only" wire:model="screenshot"></label>
            </div>
          </div>
        @elseif ($method)
          <p class="mt-4 p-4 rounded-2xl bg-surface ring-1 ring-stone-200 text-sm text-stone-700">{{ $method->instructions }}</p>
          @if ($method->isGateway())
          <label class="block mt-4"><span class="text-sm font-semibold">Phone number <span class="text-brand-600">*</span></span><input type="tel" wire:model.blur="customerPhone" placeholder="01XXXXXXXXX" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></label>
          @endif
        @endif
      </section>
    </div>

    <!-- summary -->
    <aside class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-5 md:p-6 lg:sticky lg:top-6">
      <h2 class="font-display font-semibold text-lg">Your order</h2>
      <ul class="mt-4 divide-y divide-stone-100">
        @forelse ($items as $item)
        @continue(! $item->product)
        <li class="py-3 flex gap-3 text-sm" wire:key="sum-{{ $item->id }}"><span class="grid place-items-center w-11 h-11 rounded-xl bg-gradient-to-br text-white shrink-0 {{ $item->product->gradient }}"><x-store.icon :name="$item->product->icon_key" class="w-5 h-5" /></span>
          <span class="flex-1 min-w-0"><b class="block truncate">{{ $item->product->name }}</b><span class="text-stone-500">Qty {{ $item->quantity }}</span></span>
          <b>{{ $this->money($item->lineTotal()) }}</b></li>
        @empty
        <li class="py-6 text-center text-sm text-stone-500">Cart is empty — <a href="{{ route('home') }}" class="text-brand-700 font-semibold">add products</a></li>
        @endforelse
      </ul>
      <div class="mt-3 flex gap-2"><input wire:model="coupon" placeholder="Coupon code" class="flex-1 h-11 rounded-xl border-stone-200 uppercase placeholder:normal-case focus:border-brand-500 focus:ring-brand-500"><button type="button" wire:click="applyCoupon" class="h-11 px-4 rounded-xl bg-ink text-white font-semibold">Apply</button></div>
      @if ($couponMsg)<p class="mt-1 text-xs font-semibold {{ $couponOk ? 'text-emerald-700' : 'text-red-600' }}">{{ $couponMsg }}</p>@endif
      <dl class="mt-5 space-y-2 text-sm">
        <div class="flex justify-between"><dt class="text-stone-600">Subtotal</dt><dd class="font-semibold">{{ $this->money($this->subtotal()) }}</dd></div>
        @if ($couponOk)<div class="flex justify-between text-emerald-700"><dt>Discount</dt><dd class="font-semibold">− {{ $this->money($discount) }}</dd></div>@endif
        @if ($this->fee())<div class="flex justify-between"><dt class="text-stone-600">Processing fee</dt><dd class="font-semibold">{{ $this->money($this->fee()) }}</dd></div>@endif
        <div class="flex justify-between items-end pt-3 border-t border-dashed border-stone-300"><dt class="font-semibold">Total</dt><dd class="font-display text-2xl font-bold text-brand-700">{{ $this->money($this->total()) }}</dd></div>
      </dl>
      @if ($error)<p class="mt-4 p-3 rounded-xl bg-red-50 text-red-700 text-sm font-semibold">{{ $error }}</p>@endif
      <button type="submit" class="mt-5 w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/25 hover:from-brand-700 hover:to-brand-900"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Place order · <span>{{ $this->money($this->total()) }}</span></button>
      <p class="mt-3 text-xs text-center text-stone-500">By placing an order you agree to our <a href="{{ url('/page/terms') }}" class="underline">Terms</a> &amp; <a href="{{ url('/page/refund-policy') }}" class="underline">Refund policy</a>.</p>
    </aside>
  </form>
