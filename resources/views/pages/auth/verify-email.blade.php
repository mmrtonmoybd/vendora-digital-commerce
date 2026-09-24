<x-layouts.auth
  :seo-title="__('account.verify_your_email')"
  :heading="__('account.verify_your_email')"
  :subheading="__('account.verify_sub')"
  :panel-title="__('account.check_your_inbox')"
  :panel-lead="__('account.verify_panel_lead')">
  <div class="panel panel-p text-center">
    <span class="mx-auto grid place-items-center w-16 h-16 rounded-[var(--radius-panel)] bg-navy-50 text-brand-600 float">
      <x-store.ui name="mail" class="w-8 h-8" /></span>

    <p class="mt-4 text-navy-600">{{ __('account.verification_sent_to') }} <b
        class="text-navy-900">{{ auth()->user()?->email }}</b>{{ __('account.click_to_activate') }}</p>

    @if (session('status') === 'verification-link-sent')
      <div class="note note-ok mt-4 flex items-center justify-center gap-2 font-semibold">
        <x-store.ui name="check-circle" class="w-4 h-4" />{{ __('account.new_link_sent') }}
      </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">@csrf
      <button type="submit" class="btn btn-primary btn-lg btn-block sheen mt-5"><x-store.ui name="refresh"
          class="w-4 h-4" />{{ __('account.resend_verification') }}</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
      <button type="submit" class="btn btn-ghost btn-block">{{ __('store.log_out') }}</button>
    </form>
  </div>
</x-layouts.auth>
