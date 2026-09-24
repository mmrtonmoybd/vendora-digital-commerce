{{-- "I didn't get the invite". Reached from a signed link in the delivery email, so it has
     to work for a guest with no session. The form posts to the same signed route. --}}
<x-layouts.storefront
  :seo-title="__('access.problem_title')"
  seo-robots="noindex,nofollow"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="__('access.problem_title')" :crumbs="[__('access.problem_title') => null]" padding="pb-24" />
  </x-slot:hero>

  <div class="wrap-narrow">
    <div class="reveal panel panel-p">
      <p class="lede">{{ __('access.problem_intro') }}</p>

      {{-- What the shop already holds against this grant, ruled as terms, so nobody has to
           type an order number from memory to be taken seriously. --}}
      <p class="label-line mt-6">{{ __('access.on_file') }}</p>
      <dl class="mt-1">
        <div class="kv">
          <dt>{{ __('account.order_number') }}</dt>
          <dd class="tnum">{{ $order?->order_number }}</dd>
        </div>
        <div class="kv">
          <dt>{{ __('invoice.item') }}</dt>
          <dd class="shrink">{{ $item->name() }}</dd>
        </div>
        <div class="kv">
          <dt>{{ __('account.access_email') }}</dt>
          <dd class="shrink break-all">{{ $item->delivery_email }}</dd>
        </div>
      </dl>

      <div class="tear mt-6 pt-6">
        <x-store.auth-errors />

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

          <button type="submit" class="group btn btn-primary btn-lg btn-block sheen"><x-store.ui name="send"
              class="w-4 h-4 icon-nudge" />{{ __('access.report_problem') }}</button>
        </form>
      </div>

      <p class="mt-4 text-center text-xs text-ink-500">{{ __('access.problem_footer') }}</p>
    </div>
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
