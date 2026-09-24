<x-layouts.auth
  :seo-title="__('account.forgot_password')"
  :heading="__('account.forgot_heading')"
  :subheading="__('account.forgot_sub')"
  :panel-title="__('account.it_happens')"
  :panel-lead="__('account.forgot_panel_lead')">
  <x-store.auth-errors />

  <form class="space-y-4" method="POST" action="{{ route('password.email') }}">
    @csrf
    <label class="block"><span class="field-label">{{ __('account.email_address') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="mail" class="field-affix w-4 h-4" />
        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required class="field field-icon"></span></label>

    <button type="submit" class="btn btn-primary btn-lg btn-block sheen"><x-store.ui name="send"
        class="w-4 h-4" />{{ __('account.email_me_reset_link') }}</button>

    <p class="text-center text-sm"><a href="{{ route('login') }}" class="link">{{ __('account.back_to_sign_in') }}</a></p>
  </form>
</x-layouts.auth>
