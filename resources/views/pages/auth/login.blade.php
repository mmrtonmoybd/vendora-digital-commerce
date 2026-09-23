@php $auth = app(\App\Settings\AuthSettings::class); @endphp

<x-layouts.auth :seo-title="__('account.sign_in')" :heading="__('account.sign_in')" :subheading="__('account.sign_in_sub')">
        @if ($auth->google_login_enabled && filled($auth->google_client_id))
        <a href="{{ route('auth.google') }}" class="w-full h-12 inline-flex items-center justify-center gap-3 rounded-xl bg-white ring-1 ring-stone-200 font-semibold hover:bg-stone-50"><svg class="w-5 h-5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 12h6"/><path d="M18 12a6 6 0 1 1-1.8-4.3"/></svg> {{ __('account.continue_with_google') }}</a>
        <div class="my-6 flex items-center gap-3 text-xs text-stone-400 uppercase tracking-wider"><span class="flex-1 h-px bg-stone-200"></span>{{ __('account.or') }}<span class="flex-1 h-px bg-stone-200"></span></div>
        @endif
        <x-store.auth-errors />
        <form class="space-y-4" x-data="{show:false}" method="POST" action="{{ route('login.store') }}">
          @csrf
          <label class="block"><span class="text-sm font-semibold">{{ __('account.email_address') }} <span class="text-brand-600">*</span></span>
      <span class="relative block mt-1.5"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span><input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required class="w-full h-11 pl-10 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span></label>
          <label class="block"><span class="flex justify-between text-sm font-semibold">{{ __('account.password') }} <a href="{{ route('password.request') }}" class="text-brand-700 font-semibold hover:underline">{{ __('account.forgot') }}</a></span>
            <span class="relative block mt-1.5"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            <input :type="show?'text':'password'" name="password" required placeholder="••••••••" class="w-full h-11 pl-10 pr-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500">
            <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-brand-600" aria-label="{{ __('account.show_password') }}"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></button></span></label>
          <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="rounded text-brand-600 focus:ring-brand-500"> {{ __('account.keep_me_signed_in') }}</label>
          <button type="submit" class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/25 hover:from-brand-700 hover:to-brand-900">{{ __('account.sign_in') }}</button>
        </form>
        @if ($auth->registration_enabled)
        <p class="mt-6 text-center text-sm text-stone-600">{{ __('account.new_here') }} <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">{{ __('account.create_an_account') }}</a></p>
        @endif
</x-layouts.auth>
