---
name: template-slicing
description: Use whenever creating or editing any storefront Blade view, Blade component or Livewire view that corresponds to a file in design/vendora. Enforces pixel-identical slicing of the Vendora template.
---

# Template slicing rules

- Source of truth: `design/vendora/*.html`. Never edit that folder.
- Before editing a view, open the matching template file and copy the markup verbatim.
- Allowed changes: Blade expressions replacing demo content; `@foreach`/`@if`/`@auth`/`@guest` wrappers;
  `wire:*` / `name` / `value` / `action` / `method` / `@csrf` / `@error` / `old()` / `aria-*` attributes;
  extracting repeated markup into components whose rendered HTML is identical.
- Forbidden: adding/removing/reordering classes, new wrappers that affect layout, new colors,
  new icons, new fonts, inline styles (except the brand CSS variables block from settings).
- Missing states (errors, empty lists): reuse an existing template element/class pattern.
- Gradient/colour classes that come from the DB must be one of the presets in
  `config/storefront.php` and be present in the Tailwind safelist.
- The storefront builds with Tailwind **v3** through `npm run build:storefront`, never through Vite's
  Tailwind v4 plugin (that one is only for the Filament panel theme).
- After changing a page, run: `npm run build && php artisan test --filter=VisualParity`
- Verify no class drift with: `php scripts/check-classes.php`
