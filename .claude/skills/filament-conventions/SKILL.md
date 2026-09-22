---
name: filament-conventions
description: Use whenever creating or editing Filament resources, pages, widgets, actions, panels or plugins in this project.
---

# Filament conventions

- Read `.claude/skills/filament-development/SKILL.md` first; it carries the installed-version API.
  Installed: Filament 5.8, Livewire 4.4, Laravel 13.
- Admin panel id `admin` (guard `admin`, path `/admin`). Customer panel id `customer` (guard `web`, path `/customer`).
- Every admin resource is protected by Filament Shield permissions; run `shield:generate --all --panel=admin` after adding one.
- Customer panel queries must always be scoped to `auth()->id()` (global scope or `modifyQueryUsing`) and backed by a Policy.
- Status columns use enum `label()`/`color()`/`icon()`; money columns use `Money::format()`.
- Tables: searchable, sortable, filters, per-page options `[10, 25, 50, 100]`, default sort newest first.
- Heavy widgets are cached (60s) and lazy-loaded.
- Stats widgets extend `StatsOverviewWidget` and use the `SummarisesTable` concern so the figures
  describe exactly the rows the operator is filtering.
- Panel look is configured only through the theme CSS in `resources/css/filament/admin/`
  (Tailwind v4 via Vite). Never touch the storefront's Tailwind v3 pipeline from here.
