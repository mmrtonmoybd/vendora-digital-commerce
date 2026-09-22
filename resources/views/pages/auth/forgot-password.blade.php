<x-layouts.auth
    seo-title="Forgot password"
    heading="Forgot password?"
    subheading="Enter your email and we'll send a reset link."
    panel-title="It happens."
    panel-lead="Reset your password and get straight back to your orders."
>
        <form class="mt-8 space-y-4" method="POST" action="{{ route('password.email') }}">
          @csrf
          @if (session('status'))
          <div class="p-3 rounded-xl bg-emerald-50 text-emerald-800 text-sm font-semibold ring-1 ring-emerald-200">{{ session('status') }}</div>
          @endif
          @if ($errors->any())
          <div class="p-3 rounded-xl bg-red-50 text-red-700 text-sm font-semibold ring-1 ring-red-200">{{ $errors->first() }}</div>
          @endif
          <label class="block"><span class="text-sm font-semibold">Email address <span class="text-brand-600">*</span></span>
      <span class="relative block mt-1.5"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span><input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required class="w-full h-11 pl-10 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span></label>
          <button type="submit" class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/25 hover:from-brand-700 hover:to-brand-900">Email me a reset link</button>
          <p class="text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">&larr; Back to sign in</a></p>
        </form>
</x-layouts.auth>
