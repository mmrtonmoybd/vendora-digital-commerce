<x-layouts.auth
  :seo-title="__('account.set_a_new_password')"
  :heading="__('account.set_a_new_password')"
  :subheading="__('account.reset_sub')"
  :panel-title="__('account.almost_done')"
  :panel-lead="__('account.reset_panel_lead')">
  <form class="mt-8 space-y-4" method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-store.auth-errors />
    <label class="block"><span class="text-sm font-semibold">{{ __('account.email_address') }} <span class="text-brand-600">*</span></span>
      <span class="relative block mt-1.5"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="20" height="16" x="2" y="4" rx="2" />
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
          </svg></span><input type="email" name="email" value="{{ old('email', $email) }}" placeholder="you@example.com" required
          class="w-full h-11 pl-10 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span></label>
    <label class="block"><span class="text-sm font-semibold">{{ __('account.new_password') }} <span class="text-brand-600">*</span></span>
      <span class="relative block mt-1.5"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="18" height="11" x="3" y="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg></span><input type="password" name="password" placeholder="{{ __('account.min_8_characters') }}" required
          class="w-full h-11 pl-10 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span></label>
    <label class="block"><span class="text-sm font-semibold">{{ __('account.confirm_new_password') }} <span class="text-brand-600">*</span></span>
      <span class="relative block mt-1.5"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="18" height="11" x="3" y="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg></span><input type="password" name="password_confirmation" placeholder="{{ __('account.repeat_password') }}" required
          class="w-full h-11 pl-10 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span></label>
    <button type="submit"
      class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/25 hover:from-brand-700 hover:to-brand-900">{{ __('account.reset_password') }}</button>
  </form>
</x-layouts.auth>
