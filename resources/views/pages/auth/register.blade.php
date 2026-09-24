@php $auth = app(\App\Settings\AuthSettings::class); @endphp

<x-layouts.auth
  :seo-title="__('account.create_account')"
  :heading="__('account.create_account')"
  :subheading="__('account.register_sub')"
  :panel-title="__('account.join_in_a_minute')"
  :panel-lead="__('account.register_panel_lead')">
  @if ($auth->google_login_enabled && filled($auth->google_client_id))
    <a href="{{ route('auth.google') }}" class="btn btn-quiet btn-lg btn-block">
      <x-store.ui name="globe" class="w-[18px] h-[18px] text-brand-600" />{{ __('account.continue_with_google') }}</a>
    <div class="my-6 flex items-center gap-3 text-xs text-ink-400">
      <span class="flex-1 h-px bg-ink-200"></span>{{ __('account.or') }}<span class="flex-1 h-px bg-ink-200"></span>
    </div>
  @endif

  <x-store.auth-errors />

  {{-- Eight fields is a long form, so it is ruled into the two things it actually asks for:
       who the passes will be issued to, and how this person proves they are them. --}}
  <form class="grid sm:grid-cols-2 gap-4" method="POST" action="{{ route('register.store') }}">
    @csrf

    <p class="label-line sm:col-span-2">{{ __('account.your_details') }}</p>

    <label class="block sm:col-span-2"><span class="field-label">{{ __('account.full_name') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="user" class="field-affix w-4 h-4" />
        <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('account.your_name') }}" required
          class="field field-icon"></span></label>

    <label class="block sm:col-span-2"><span class="field-label">{{ __('account.email_address') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="mail" class="field-affix w-4 h-4" />
        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required class="field field-icon"></span>
      <span class="field-hint">{{ __('account.email_receives_access') }}</span></label>

    <label class="block"><span class="field-label">{{ __('account.phone') }}</span>
      <span class="relative block"><x-store.ui name="phone" class="field-affix w-4 h-4" />
        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" class="field field-icon"></span></label>

    <label class="block"><span class="field-label">{{ __('account.referral_code') }}</span>
      <span class="relative block"><x-store.ui name="gift" class="field-affix w-4 h-4" />
        <input name="referral_code" value="{{ old('referral_code', $referralCode) }}" placeholder="{{ __('account.optional') }}"
          class="field field-icon field-code {{ $referralCode !== '' ? 'border-grant-600 bg-grant-50' : '' }}"></span>
      @if ($referralCode !== '')
        <span class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-grant-700">
          <x-store.ui name="check-circle" class="w-3.5 h-3.5" />{{ __('account.referral_applied') }}</span>
      @endif
    </label>

    <p class="label-line sm:col-span-2 pt-2">{{ __('account.choose_a_password') }}</p>

    <label class="block"><span class="field-label">{{ __('account.password') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="lock" class="field-affix w-4 h-4" />
        <input type="password" name="password" placeholder="{{ __('account.min_8_characters') }}" required class="field field-icon"></span></label>

    <label class="block"><span class="field-label">{{ __('account.confirm_password') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="lock" class="field-affix w-4 h-4" />
        <input type="password" name="password_confirmation" placeholder="{{ __('account.repeat_password') }}" required class="field field-icon"></span></label>

    <label class="sm:col-span-2 flex items-start gap-2.5 text-sm text-ink-600">
      <input type="checkbox" name="terms" value="1" required class="check mt-0.5">
      <span>{{ __('account.i_agree_to_the') }} <a href="{{ url('/page/terms') }}" class="link">{{ __('account.terms') }}</a>
        {{ __('account.and') }} <a href="{{ url('/page/privacy') }}" class="link">{{ __('account.privacy_policy') }}</a>.</span></label>

    <div class="sm:col-span-2">
      <button type="submit" data-track-click="sign_up" data-track-payload='{"value":0,"currency":"{{ \App\Support\Money::currencyCode() }}"}'
        class="group btn btn-primary btn-lg btn-block sheen">{{ __('account.create_account') }}<x-store.ui name="arrow-right"
          class="w-4 h-4 icon-nudge" /></button>
    </div>
  </form>

  <p class="mt-6 text-center text-sm text-ink-500">{{ __('account.already_have_account') }}
    <a href="{{ route('login') }}" class="link">{{ __('account.sign_in') }}</a>
  </p>
</x-layouts.auth>
