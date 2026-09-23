<x-layouts.auth
    :seo-title="__('account.verify_your_email')"
    :heading="__('account.verify_your_email')"
    :subheading="__('account.verify_sub')"
    :panel-title="__('account.check_your_inbox')"
    :panel-lead="__('account.verify_panel_lead')"
>
        <div class="mt-8 rounded-2xl bg-white ring-1 ring-stone-200 p-6 text-center">
          <span class="mx-auto grid place-items-center w-16 h-16 rounded-2xl bg-brand-50 text-brand-600"><svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
          <p class="mt-4 text-stone-700">{{ __('account.verification_sent_to') }} <b>{{ auth()->user()?->email }}</b>{{ __('account.click_to_activate') }}</p>
          @if (session('status') === 'verification-link-sent')
          <div class="mt-4 p-3 rounded-xl bg-emerald-50 text-emerald-800 text-sm font-semibold">{{ __('account.new_link_sent') }}</div>
          @endif
          <form method="POST" action="{{ route('verification.send') }}">@csrf<button type="submit" class="mt-5 w-full h-12 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold">{{ __('account.resend_verification') }}</button></form>
          <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf<button type="submit" class="w-full h-11 rounded-xl text-sm font-semibold text-stone-600 hover:bg-stone-50">{{ __('store.log_out') }}</button></form>
        </div>
</x-layouts.auth>
