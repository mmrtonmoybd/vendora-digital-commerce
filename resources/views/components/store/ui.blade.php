@props([
    'name' => 'arrow-right',
    'strokeWidth' => 2,
])

{{--
  The interface icon set.

  Product and category icons come from config/storefront.php because an administrator picks
  them; these are the other kind — the arrows, states and affordances the markup itself
  needs. They live here rather than inline in forty templates so that a chevron is the same
  chevron everywhere, and so a page can afford to use one wherever it helps.

  Every path is drawn on a 24×24 grid with round caps, so any two icons sit together at any
  size. An unknown name falls back to the arrow rather than rendering an empty box.
--}}
@php
  $paths = [
      // Direction
      'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
      'arrow-left' => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
      'arrow-up-right' => '<path d="M7 17 17 7"/><path d="M7 7h10v10"/>',
      'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
      'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
      'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',

      // Commerce
      'cart' =>
          '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
      'bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
      'tag' =>
          '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
      'wallet' =>
          '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
      'card' => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
      'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 8h8"/><path d="M8 12h6"/>',

      // State
      'check' => '<path d="M20 6 9 17l-5-5"/>',
      'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
      'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
      'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>',
      'lock' => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
      'zap' => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
      'alert' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
      'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
      'sparkle' =>
          '<path d="M12 3v4"/><path d="M12 17v4"/><path d="M3 12h4"/><path d="M17 12h4"/><path d="m5.6 5.6 2.8 2.8"/><path d="m15.6 15.6 2.8 2.8"/><path d="m18.4 5.6-2.8 2.8"/><path d="m8.4 15.6-2.8 2.8"/>',
      'refresh' => '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/>',

      // People and channels
      'user' => '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>',
      'users' =>
          '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
      'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
      'phone' =>
          '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
      'headset' => '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
      'whatsapp' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
      'telegram' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
      'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
      'youtube' => '<path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2"/>',
      'discord' =>
          '<path d="M9 12a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/><path d="M13 12a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/><path d="M8.5 6.5 7 4a15 15 0 0 0-4 2c-1 4-1 8 0 12a16 16 0 0 0 5 2l1-2"/><path d="m15.5 6.5 1.5-2.5a15 15 0 0 1 4 2c1 4 1 8 0 12a16 16 0 0 1-5 2l-1-2"/>',
      'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',

      // Objects and structure
      'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
      'grid' =>
          '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
      'layers' => '<path d="m12 2 9 5-9 5-9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
      'box' =>
          '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
      'book' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
      'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
      'globe' =>
          '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
      'currency' =>
          '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.5 2.5 0 0 0 0 5h1a2.5 2.5 0 0 1 0 5h-1A2.5 2.5 0 0 1 9 14.5"/>',
      'copy' => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
      'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
      'trash' => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
      'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
      'minus' => '<path d="M5 12h14"/>',
      'close' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
      'menu' => '<line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/>',
      'filter' => '<path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/>',
      'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
      'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
      'chart' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
      'gift' =>
          '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
      'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
      'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
  ];

  $body = $paths[$name] ?? $paths['arrow-right'];
@endphp

<svg {{ $attributes->merge(['class' => 'w-4 h-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}"
  stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $body !!}</svg>
