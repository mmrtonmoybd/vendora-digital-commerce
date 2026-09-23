@php
    $contact = $store->contact();
    $general = $store->general();

    // Only the channels that are actually configured get a card.
    $cards = array_values(array_filter([
        filled($contact->phone) ? [__('content.call_us'), $contact->phone, $store->st($general, 'support_hours'), '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>'] : null,
        filled($contact->email) ? [__('content.email_card'), $contact->email, __('content.replies_within_2_hours'), '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'] : null,
        filled($contact->whatsapp) ? [__('content.whatsapp'), $contact->whatsapp, __('content.fastest_for_order_issues'), '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>'] : null,
        filled($store->st($contact, 'address')) ? [__('content.office'), $store->st($contact, 'address'), __('content.by_appointment'), '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>'] : null,
    ]));
@endphp

<x-layouts.storefront
    :seo-title="$page?->t('seo_title') ?: __('content.contact_us')"
    :seo-description="$page?->seo_description ?: 'Talk to a real person about your order.'"
    main-class="-mt-4 relative z-10"
>
<x-slot:hero>
    <x-store.page-hero title="{{ $page?->title ?: 'Contact Us' }}" lead="Talk to a real person — we usually reply the same day." :crumbs="['Contact' => null]" />
</x-slot:hero>

  <div class="max-w-7xl mx-auto px-4">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">@foreach ($cards as [$label, $value, $note, $icon])<div class="bg-white rounded-2xl p-5 shadow-card ring-1 ring-black/5 flex gap-4"><span class="shrink-0 grid place-items-center w-11 h-11 rounded-xl bg-brand-600 text-white"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg></span><div class="min-w-0"><p class="text-xs uppercase tracking-wider font-bold text-stone-500">{{ $label }}</p><p class="font-semibold truncate">{{ $value }}</p><p class="text-xs text-stone-500">{{ $note }}</p></div></div>@endforeach</div>
    <div class="mt-8 grid lg:grid-cols-[1.2fr_1fr] gap-6">
      <form method="POST" action="{{ route('contact.store') }}" class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-6 md:p-8 grid sm:grid-cols-2 gap-4">
        @csrf
        {{-- Honeypot: a person never sees this, a bot fills everything it finds. --}}
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true">
        <div class="sm:col-span-2"><h2 class="font-display text-2xl font-bold">{{ __('content.send_us_a_message') }}</h2><p class="text-sm text-stone-600">{{ __('content.we_usually_reply') }}</p></div>
        <label class="block"><span class="text-sm font-semibold">{{ __('content.your_name') }} <span class="text-brand-600">*</span></span>
      <span class="relative block mt-1.5"><input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('account.full_name') }}" required class="w-full h-11  rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span>@error('name')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror</label>
        <label class="block"><span class="text-sm font-semibold">{{ __('content.email') }} <span class="text-brand-600">*</span></span>
      <span class="relative block mt-1.5"><input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required class="w-full h-11  rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span>@error('email')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror</label>
        <label class="block"><span class="text-sm font-semibold">{{ __('content.phone') }}</span>
      <span class="relative block mt-1.5"><input type="tel" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" class="w-full h-11  rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"></span>@error('phone')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror</label>
        <label class="block"><span class="text-sm font-semibold">{{ __('content.subject') }}</span><select name="subject" class="mt-1.5 w-full h-11 rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500"><option>{{ __('content.subjects.order') }}</option><option>{{ __('content.subjects.payment') }}</option><option>{{ __('content.subjects.bulk') }}</option><option>{{ __('content.subjects.other') }}</option></select></label>
        <label class="block sm:col-span-2"><span class="text-sm font-semibold">{{ __('content.message') }} <span class="text-brand-600">*</span></span><textarea name="message" rows="5" required class="mt-1.5 w-full rounded-xl border-stone-200 focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('content.message_placeholder') }}">{{ old('message') }}</textarea>@error('message')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror</label>
        <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center gap-3"><button type="submit" class="sm:w-auto px-8 h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-800 text-white font-semibold shadow-lg shadow-brand-700/25 hover:from-brand-700 hover:to-brand-900" data-track-click="contact" data-track-payload='{"value":0,"currency":"{{ \App\Support\Money::currencyCode() }}"}'><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg> {{ __('content.send_message') }}</button>
          @if (session('status'))<p class="text-sm font-semibold text-emerald-700">{{ session('status') }}</p>@endif</div>
      </form>
      @if (filled($contact->map_embed_url))
      <div class="rounded-3xl overflow-hidden shadow-card ring-1 ring-black/5 bg-white min-h-[360px]">
        <iframe title="{{ __('content.office_location') }}" class="w-full h-full min-h-[360px] grayscale-[30%]" loading="lazy" src="{{ $contact->map_embed_url }}"></iframe>
      </div>
      @endif
    </div>
  </div>
<div class="h-20"></div>
</x-layouts.storefront>
