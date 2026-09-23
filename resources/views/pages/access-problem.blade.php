{{-- "I didn't get the invite". Reached from a signed link in the delivery email, so it has
     to work for a guest with no session. The form posts to the same signed route. --}}
<x-layouts.storefront
  :seo-title="__('access.problem_title')"
  seo-robots="noindex,nofollow"
  main-class="-mt-4 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="__('access.problem_title')" :crumbs="[__('access.problem_title') => null]" />
  </x-slot:hero>

  <div class="max-w-2xl mx-auto px-4">
    <div class="mt-8 bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-6 md:p-8">
      <p class="text-stone-600">{{ __('access.problem_intro') }}</p>

      <div class="mt-4 p-4 rounded-2xl bg-surface ring-1 ring-stone-200 text-sm">
        <p><span class="text-stone-500">{{ __('account.order_number') }}:</span> <b>{{ $order?->order_number }}</b></p>
        <p class="mt-1"><span class="text-stone-500">{{ __('invoice.item') }}:</span> <b>{{ $item->name() }}</b></p>
        <p class="mt-1"><span class="text-stone-500">{{ __('account.access_email') }}</span> <b>{{ $item->delivery_email }}</b></p>
      </div>

      <x-store.auth-errors />

      <form method="POST" action="{{ route('access.problem.store', $item) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
        @csrf

        <label class="block"><span class="text-sm font-semibold">{{ __('access.what_happened') }} <span class="text-brand-600">*</span></span>
          <select name="reason" required class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500">
            @foreach ($reasons as $reason)
              <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
            @endforeach
          </select>
        </label>

        <label class="block"><span class="text-sm font-semibold">{{ __('access.more_detail') }}</span>
          <textarea name="message" rows="4" class="mt-1.5 w-full rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"
            placeholder="{{ __('access.more_detail_placeholder') }}">{{ old('message') }}</textarea>
        </label>

        <label class="block"><span class="text-sm font-semibold">{{ __('access.screenshot') }}</span>
          <input type="file" name="screenshot" accept="image/*"
            class="mt-1.5 w-full rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500">
          <span class="mt-1 block text-xs text-stone-500">{{ __('shop.screenshot_hint') }}</span>
        </label>

        <button type="submit"
          class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/25 hover:from-brand-700 hover:to-brand-900">{{ __('access.report_problem') }}</button>
      </form>

      <p class="mt-4 text-center text-xs text-stone-500">{{ __('access.problem_footer') }}</p>
    </div>
  </div>
  <div class="h-20"></div>
</x-layouts.storefront>
