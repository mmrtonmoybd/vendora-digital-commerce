<x-layouts.auth
  :seo-title="__('account.verify_your_email')"
  :heading="__('account.verify_your_email')"
  :subheading="__('account.verify_sub')"
  :panel-title="__('account.check_your_inbox')"
  :panel-lead="__('account.verify_panel_lead')">
  {{--
    An account waiting on its email is an unstamped pass: issued, but not yet countersigned
    by the person it names. So the screen is the pass itself — artwork above the perforation,
    the terms and the two things you can do about it below — with the hold stamp pressed on
    top until the link is clicked.
  --}}
  <div class="pass relative" style="--tear: 156px">
    <div class="ribbon ribbon-amber"><span>{{ __('account.stamp_unverified') }}</span></div>

    <div class="well h-[156px] rounded-t-[var(--radius-panel)]">
      <span class="grid place-items-center w-16 h-16 rounded-[var(--radius-panel)] bg-ink-900 text-white float">
        <x-store.ui name="mail" class="w-8 h-8" /></span>
    </div>

    <div class="tear px-5 md:px-6 pt-5 pb-6 text-center">
      <p class="text-ink-600">{{ __('account.verification_sent_to') }} <b
          class="text-ink-900">{{ auth()->user()?->email }}</b>{{ __('account.click_to_activate') }}</p>

      @if (session('status') === 'verification-link-sent')
        <div role="status" class="note note-ok mt-4 flex items-center justify-center gap-2 font-semibold">
          <x-store.ui name="check-circle" class="w-4 h-4" />{{ __('account.new_link_sent') }}
        </div>
      @endif

      <form method="POST" action="{{ route('verification.send') }}">@csrf
        <button type="submit" class="group btn btn-primary btn-lg btn-block sheen mt-5"><x-store.ui name="refresh"
            class="w-4 h-4 icon-spin" />{{ __('account.resend_verification') }}</button>
      </form>
      <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
        <button type="submit" class="btn btn-ghost btn-block">{{ __('store.log_out') }}</button>
      </form>
    </div>
  </div>
</x-layouts.auth>
