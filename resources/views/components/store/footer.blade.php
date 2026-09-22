@php
    $general = $store->general();
    $contact = $store->contact();
    $footerMenus = $store->footerMenus();
    $methods = $store->footerPaymentMethods();

    $socials = array_filter([
        'WhatsApp' => filled($contact->whatsapp) ? 'https://wa.me/'.preg_replace('/\D/', '', $contact->whatsapp) : null,
        'Telegram' => filled($contact->telegram) ? 'https://t.me/'.ltrim($contact->telegram, '@') : null,
        'Facebook' => $contact->facebook,
        'YouTube' => $contact->youtube,
        'Discord' => $contact->discord,
    ]);

    $socialIcons = [
        'WhatsApp' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'Telegram' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'Facebook' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'YouTube' => '<path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2"/>',
        'Discord' => '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
    ];
@endphp

<!-- ================= FOOTER ================= -->
<footer class="mt-auto relative bg-[#1a0d0e] text-stone-400 pt-20">
  <svg class="absolute top-0 left-0 w-full h-10 -translate-y-[98%] text-[#1a0d0e]" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0,40 C300,0 600,60 900,30 C1150,5 1300,30 1440,20 L1440,60 L0,60 Z"/></svg>
  <div class="max-w-7xl mx-auto px-4 grid gap-10 lg:grid-cols-[1.3fr_repeat(4,1fr)]">
    <div>
      <a href="{{ route('home') }}" class="font-script text-4xl text-white leading-none tracking-wide select-none" aria-label="{{ $general->site_name }} home">{{ $general->site_name }}<span class="text-amber-300">.</span></a>
      <p class="mt-4 text-sm leading-relaxed max-w-xs">{{ $general->tagline }}</p>
      <div class="mt-5 space-y-2 text-sm">
        @if (filled($contact->phone))
        <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}" class="flex items-center gap-2 hover:text-white"><svg class="w-4 h-4 text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> {{ $contact->phone }}</a>
        @endif
        @if (filled($contact->email))
        <a href="mailto:{{ $contact->email }}" class="flex items-center gap-2 hover:text-white"><svg class="w-4 h-4 text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> {{ $contact->email }}</a>
        @endif
      </div>
      <div class="mt-5 flex flex-wrap gap-2">@foreach ($socials as $label => $url)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $label }}" class="grid place-items-center w-10 h-10 rounded-full bg-white/5 ring-1 ring-white/10 hover:bg-brand-600 hover:ring-brand-600 transition"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $socialIcons[$label] !!}</svg></a>@endforeach</div>
    </div>
    @foreach ($footerMenus as $menu)
<div><h4 class="font-display font-semibold text-white mb-4">{{ $menu->column_title ?: $menu->name }}</h4><ul class="space-y-2.5 text-sm">@foreach ($menu->items as $item)<li><a href="{{ $item->resolvedUrl() }}"@if ($item->open_in_new_tab) target="_blank" rel="noopener"@endif class="hover:text-white hover:translate-x-0.5 inline-block transition">{{ $item->label }}</a></li>@endforeach</ul></div>
@endforeach
  </div>
  <div class="mt-14 border-t border-white/10">
    <div class="max-w-7xl mx-auto px-4 py-6 flex flex-col md:flex-row gap-3 items-center justify-between text-sm">
      <p>{{ $general->copyright_text }}</p>
      <div class="flex items-center gap-2 text-xs"><span class="text-stone-500">We accept</span>
        @foreach ($methods as $method)<span class="px-2 py-1 rounded {{ $method->chip_color }} text-white font-bold">{{ $method->name }}</span>@endforeach</div>
    </div>
  </div>
</footer>
