@php $auth = app(\App\Settings\AuthSettings::class); @endphp

<x-layouts.auth :seo-title="__('account.sign_in')" :heading="__('account.sign_in')" :subheading="__('account.sign_in_sub')">
  @if ($auth->google_login_enabled && filled($auth->google_client_id))
    <a href="{{ route('auth.google') }}" class="btn btn-quiet btn-lg btn-block">
      <x-store.ui name="globe" class="w-[18px] h-[18px] text-brand-600" />{{ __('account.continue_with_google') }}</a>
    <div class="my-6 flex items-center gap-3 text-xs text-ink-400">
      <span class="flex-1 h-px bg-ink-200"></span>{{ __('account.or') }}<span class="flex-1 h-px bg-ink-200"></span>
    </div>
  @endif

  <x-store.auth-errors />

  <form class="space-y-4" x-data="{ show: false }" method="POST" action="{{ route('login.store') }}">
    @csrf

    <label class="block"><span class="field-label">{{ __('account.email_address') }} <span class="field-req">*</span></span>
      <span class="relative block"><x-store.ui name="mail" class="field-affix w-4 h-4" />
        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required class="field field-icon"></span></label>

    <label class="block">
      <span class="field-label flex justify-between">{{ __('account.password') }}
        <a href="{{ route('password.request') }}" class="link font-medium">{{ __('account.forgot') }}</a></span>
      <span class="relative block"><x-store.ui name="lock" class="field-affix w-4 h-4" />
        <input :type="show ? 'text' : 'password'" name="password" required placeholder="••••••••" class="field field-icon pr-11">
        <button type="button" @click="show=!show" class="tap absolute right-3 top-1/2 -translate-y-1/2 text-ink-400 hover:text-brand-600 transition"
          :aria-label="show ? @js(__('account.hide_password')) : @js(__('account.show_password'))"><x-store.ui name="eye" class="w-4 h-4" /></button></span>
    </label>

    <label class="flex items-center gap-2 text-sm text-ink-600"><input type="checkbox" name="remember" class="check">
      {{ __('account.keep_me_signed_in') }}</label>

    <button type="submit" class="group btn btn-primary btn-lg btn-block sheen">{{ __('account.sign_in') }}
      <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></button>
  </form>

  @if ($auth->registration_enabled)
    <p class="mt-6 text-center text-sm text-ink-500">{{ __('account.new_here') }}
      <a href="{{ route('register') }}" class="link">{{ __('account.create_an_account') }}</a>
    </p>
  @endif
</x-layouts.auth>
