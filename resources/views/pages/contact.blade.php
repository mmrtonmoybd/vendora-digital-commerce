@php
  $contact = $store->contact();
  $general = $store->general();

  // Only the channels that are actually configured get a card.
  $cards = array_values(
      array_filter([
          filled($contact->phone) ? ['phone', __('content.call_us'), $contact->phone, $store->st($general, 'support_hours')] : null,
          filled($contact->email) ? ['mail', __('content.email_card'), $contact->email, __('content.replies_within_2_hours')] : null,
          filled($contact->whatsapp) ? ['whatsapp', __('content.whatsapp'), $contact->whatsapp, __('content.fastest_for_order_issues')] : null,
          filled($store->st($contact, 'address')) ? ['pin', __('content.office'), $store->st($contact, 'address'), __('content.by_appointment')] : null,
      ]),
  );
@endphp

<x-layouts.storefront
  :seo-title="$page?->t('seo_title') ?: __('content.contact_us')"
  :seo-description="$page?->seo_description ?: __('content.contact_lead')"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero
      :title="$page?->title ?: __('content.contact_us')"
      :lead="__('content.contact_lead')"
      :crumbs="[__('content.contact_us') => null]"
      padding="pb-24" />
  </x-slot:hero>

  <div class="wrap">
    {{-- Each channel is a term and its value — a number, an address, a room — so it is set
         as a ruled row rather than as a marketing tile, and the line underneath says when
         somebody is actually on the other end of it. --}}
    <div class="stagger grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      @foreach ($cards as [$icon, $label, $value, $note])
        <div class="reveal panel panel-hover p-5" style="--i: {{ $loop->index }}">
          <span class="grid place-items-center w-11 h-11 rounded-[var(--radius-control)] bg-ink-900 text-white">
            <x-store.ui :name="$icon" class="w-5 h-5" /></span>

          <dl class="mt-4">
            <div class="kv py-0">
              <dt>{{ $label }}</dt>
              <dd class="tnum min-w-0 text-right break-words">{{ $value }}</dd>
            </div>
          </dl>

          <p class="mt-1.5 text-xs text-ink-500">{{ $note }}</p>
        </div>
      @endforeach
    </div>

    <div class="mt-8 grid lg:grid-cols-[1.2fr_1fr] gap-6 items-start">
      <form method="POST" action="{{ route('contact.store') }}" class="reveal panel p-6 md:p-8 grid sm:grid-cols-2 gap-4">
        @csrf
        {{-- Honeypot: a person never sees this, a bot fills everything it finds. --}}
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true">

        <x-store.section-head
          class="sm:col-span-2"
          :label="__('content.support_desk')"
          :title="__('content.send_us_a_message')"
          :lead="__('content.we_usually_reply')" />

        <label class="block"><span class="field-label">{{ __('content.your_name') }} <span class="field-req">*</span></span>
          <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('account.full_name') }}" required class="field">
          @error('name')
            <span class="field-error">{{ $message }}</span>
          @enderror
        </label>

        <label class="block"><span class="field-label">{{ __('content.email') }} <span class="field-req">*</span></span>
          <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required class="field">
          @error('email')
            <span class="field-error">{{ $message }}</span>
          @enderror
        </label>

        <label class="block"><span class="field-label">{{ __('content.phone') }}</span>
          <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" class="field tnum">
          @error('phone')
            <span class="field-error">{{ $message }}</span>
          @enderror
        </label>

        <label class="block"><span class="field-label">{{ __('content.subject') }}</span>
          <select name="subject" class="field">
            <option>{{ __('content.subjects.order') }}</option>
            <option>{{ __('content.subjects.payment') }}</option>
            <option>{{ __('content.subjects.bulk') }}</option>
            <option>{{ __('content.subjects.other') }}</option>
          </select></label>

        <label class="block sm:col-span-2"><span class="field-label">{{ __('content.message') }} <span class="field-req">*</span></span>
          <textarea name="message" rows="5" required class="field" placeholder="{{ __('content.message_placeholder') }}">{{ old('message') }}</textarea>
          @error('message')
            <span class="field-error">{{ $message }}</span>
          @enderror
        </label>

        <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center gap-3">
          <button type="submit" class="btn btn-primary btn-lg sheen" data-track-click="contact"
            data-track-payload='{"value":0,"currency":"{{ \App\Support\Money::currencyCode() }}"}'>
            <x-store.ui name="send" class="w-4 h-4" />{{ __('content.send_message') }}</button>
          @if (session('status'))
            <p class="note note-ok flex items-center gap-2 font-semibold">
              <x-store.ui name="check-circle" class="w-4 h-4 shrink-0" />{{ session('status') }}
            </p>
          @endif
        </div>
      </form>

      @if (filled($contact->map_embed_url))
        <div class="reveal panel overflow-hidden min-h-[380px]">
          <iframe title="{{ __('content.office_location') }}" class="w-full h-full min-h-[380px]" loading="lazy"
            src="{{ $contact->map_embed_url }}"></iframe>
        </div>
      @endif
    </div>
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
