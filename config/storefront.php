<?php

declare(strict_types=1);

/**
 * The closed sets of presentation values an administrator may choose from.
 *
 * Nothing the admin types ever becomes a CSS class: every gradient, chip colour, badge style
 * and icon is picked from these lists, so the Tailwind safelist stays finite and no one can
 * break the template by pasting an arbitrary class into a text field.
 *
 * The SVG bodies below are copied verbatim from design/vendora — do not redraw them.
 */
return [

    /*
     | Product / category icons. Value is the inner markup of the template's <svg>, so the
     | wrapper (size, stroke width) stays with the component that renders it.
     */
    'icons' => [
        'play' => [
            'label' => 'Play (streaming)',
            'svg' => '<polygon points="6 3 20 12 6 21 6 3"/>',
        ],
        'key' => [
            'label' => 'Key (licenses)',
            'svg' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
        ],
        'gamepad' => [
            'label' => 'Gamepad (gaming)',
            'svg' => '<line x1="6" x2="10" y1="12" y2="12"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="15" x2="15.01" y1="13" y2="13"/><line x1="18" x2="18.01" y1="11" y2="11"/><rect width="20" height="12" x="2" y="6" rx="2"/>',
        ],
        'gift' => [
            'label' => 'Gift (gift cards)',
            'svg' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        ],
        'shield' => [
            'label' => 'Shield (VPN & security)',
            'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>',
        ],
        'pen' => [
            'label' => 'Pen (design & AI)',
            'svg' => '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/>',
        ],
        'cloud' => [
            'label' => 'Cloud (storage)',
            'svg' => '<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>',
        ],
        'book' => [
            'label' => 'Book (courses)',
            'svg' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
        ],
        'wallet' => [
            'label' => 'Wallet',
            'svg' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        ],
    ],

    /*
     | Card / tile gradients. Keys are stored in the DB; the class string is what renders.
     */
    'gradients' => [
        'from-rose-500 to-red-700' => 'Rose → Red',
        'from-orange-500 to-red-600' => 'Orange → Red',
        'from-fuchsia-600 to-rose-600' => 'Fuchsia → Rose',
        'from-amber-500 to-orange-600' => 'Amber → Orange',
        'from-stone-700 to-stone-900' => 'Stone (dark)',
        'from-red-600 to-pink-600' => 'Red → Pink',
        'from-teal-600 to-emerald-700' => 'Teal → Emerald',
        'from-red-800 to-stone-900' => 'Deep red → Stone',
    ],

    /*
     | Hero slide backgrounds, which use a different palette from product cards.
     */
    'hero_gradients' => [
        'from-amber-300 to-amber-400' => ['label' => 'Amber', 'text' => 'text-ink'],
        'from-white to-brand-50' => ['label' => 'White → Brand', 'text' => 'text-ink'],
        'from-stone-900 to-stone-800' => ['label' => 'Stone (dark)', 'text' => 'text-white'],
    ],

    /*
     | Corner ribbon variants from assets/app.css.
     */
    'badge_styles' => [
        'brand' => ['label' => 'Brand (red)', 'class' => ''],
        'amber' => ['label' => 'Amber', 'class' => 'ribbon-amber'],
        'dark' => ['label' => 'Dark', 'class' => 'ribbon-dark'],
    ],

    /*
     | Payment-method chip colours used in the footer strip and the method pickers.
     */
    'chip_colors' => [
        'bg-stone-900' => 'Stone (wallet)',
        'bg-pink-600' => 'Pink (bKash)',
        'bg-orange-600' => 'Orange (Nagad)',
        'bg-violet-700' => 'Violet (Rocket)',
        'bg-brand-700' => 'Brand (card / online)',
        'bg-emerald-600' => 'Emerald',
        'bg-sky-600' => 'Sky',
    ],

    /*
     | Blog category chips.
     */
    'chip_classes' => [
        'bg-brand-50 text-brand-700' => 'Brand',
        'bg-amber-50 text-amber-700' => 'Amber',
        'bg-emerald-50 text-emerald-700' => 'Emerald',
        'bg-sky-50 text-sky-700' => 'Sky',
        'bg-stone-100 text-stone-700' => 'Stone',
    ],

    /*
     | Testimonial avatar swatches.
     */
    'avatar_colors' => [
        'bg-amber-300' => 'Amber',
        'bg-rose-300' => 'Rose',
        'bg-emerald-300' => 'Emerald',
        'bg-sky-300' => 'Sky',
        'bg-stone-300' => 'Stone',
    ],

    /*
     | Sort options on the category and search pages. Key is the query string value.
     */
    'sorts' => [
        'popular' => 'Most popular',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'newest' => 'Newest first',
    ],
];
