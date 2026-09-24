@props(['category', 'count' => null])

{{--
  A category tile.

  The category's own artwork fills the tile behind a dark scrim — these are platform logos
  on their own backgrounds, several of them white, and the label is white text. The scrim
  lifts a little under the cursor while the artwork grows, so the tile reveals what it is
  as you reach for it.
--}}
<a href="{{ route('category.show', $category) }}"
  class="group relative overflow-hidden rounded-[var(--radius-panel)] bg-navy-900 text-white min-h-[140px] p-4 md:p-5 flex flex-col justify-between panel-hover border border-navy-900">
  @if ($category->image)
    <img src="{{ Storage::disk('public')->url($category->image) }}" alt="" loading="lazy" class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
    <span class="absolute inset-0 bg-navy-950/70 group-hover:bg-navy-950/55 transition duration-300"></span>
  @else
    <span class="absolute inset-0 hero-ground"></span>
    <span class="absolute -right-8 -top-8 w-28 h-28 rounded-full border border-white/10 spin-slow"></span>
  @endif

  <span class="relative grid place-items-center w-11 h-11 rounded-[var(--radius-control)] bg-white/15 border border-white/20 backdrop-blur-sm">
    <x-store.icon :name="$category->icon_key" class="w-5 h-5" /></span>

  <span class="relative">
    @if ($count !== null)
      <span class="tnum block text-[11.5px] text-white/60">{{ __('shop.products_count', ['count' => $count]) }}</span>
    @endif
    <span class="flex items-end justify-between gap-2">
      <span class="font-display font-semibold leading-tight">{{ $category->t('name') }}</span>
      <x-store.ui name="arrow-right" class="w-4 h-4 shrink-0 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition" />
    </span>
  </span>
</a>
