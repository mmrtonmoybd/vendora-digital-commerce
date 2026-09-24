<x-layouts.auth
  :seo-title="__('account.set_a_new_password')"
  :heading="__('account.set_a_new_password')"
  :subheading="__('account.reset_sub')"
  :panel-title="__('account.almost_done')"
  :panel-lead="__('account.reset_panel_lead')">
  <x-store.auth-errors />

  <form class="space-y-4" method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <label class="block"><span class="field-label">{{ __('account.email_address') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="mail" class="field-affix w-4 h-4" />
        <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="you@example.com" required class="field field-icon"></span></label>

    <label class="block"><span class="field-label">{{ __('account.new_password') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="lock" class="field-affix w-4 h-4" />
        <input type="password" name="password" placeholder="{{ __('account.min_8_characters') }}" required class="field field-icon"></span></label>

    <label class="block"><span class="field-label">{{ __('account.confirm_new_password') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="lock" class="field-affix w-4 h-4" />
        <input type="password" name="password_confirmation" placeholder="{{ __('account.repeat_password') }}" required class="field field-icon"></span></label>

    <button type="submit" class="btn btn-primary btn-lg btn-block sheen">{{ __('account.reset_password') }}</button>
  </form>
</x-layouts.auth>
