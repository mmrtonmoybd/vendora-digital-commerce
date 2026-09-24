{{-- "I didn't get the invite". Reached from a signed link in the delivery email, so it has
     to work for a guest with no session. The form posts to the same signed route. --}}
<x-layouts.storefront
  :seo-title="__('access.problem_title')"
  seo-robots="noindex,nofollow"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="__('access.problem_title')" :crumbs="[__('access.problem_title') => null]" padding="pb-24" />
  </x-slot:hero>

  <div class="max-w-2xl mx-auto px-4">
    <div class="panel panel-p">
      <p class="text-navy-600 leading-relaxed">{{ __('access.problem_intro') }}</p>

      {{-- What we already know, so nobody has to type an order number from memory. --}}
      <dl class="panel mt-5 p-4 bg-navy-50/60 divide-y divide-navy-100">
        <div class="kv">
          <dt>{{ __('account.order_number') }}</dt>
          <dd class="tnum">{{ $order?->order_number }}</dd>
        </div>
        <div class="kv">
          <dt>{{ __('invoice.item') }}</dt>
          <dd>{{ $item->name() }}</dd>
        </div>
        <div class="kv">
          <dt>{{ __('account.access_email') }}</dt>
          <dd>{{ $item->delivery_email }}</dd>
        </div>
      </dl>

      <div class="mt-6"><x-store.auth-errors /></div>

      <form method="POST" action="{{ route('access.problem.store', $item) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <label class="block"><span class="field-label">{{ __('access.what_happened') }} <span class="field-req">*</span></span>
          <select name="reason" required class="field">
            @foreach ($reasons as $reason)
              <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
            @endforeach
          </select></label>

        <label class="block"><span class="field-label">{{ __('access.more_detail') }}</span>
          <textarea name="message" rows="4" class="field" placeholder="{{ __('access.more_detail_placeholder') }}">{{ old('message') }}</textarea>
        </label>

        <label class="block"><span class="field-label">{{ __('access.screenshot') }}</span>
          <input type="file" name="screenshot" accept="image/*" class="field h-auto py-2.5">
          <span class="field-hint">{{ __('shop.screenshot_hint') }}</span></label>

        <button type="submit" class="btn btn-primary btn-lg btn-block sheen"><x-store.ui name="send"
            class="w-4 h-4" />{{ __('access.report_problem') }}</button>
      </form>

      <p class="mt-4 text-center text-xs text-navy-500">{{ __('access.problem_footer') }}</p>
    </div>
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
