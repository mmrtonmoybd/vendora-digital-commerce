@props(['category', 'count' => null])

{{--
  A category tile.

  The artwork here is a platform logo, and a logo is usually a wide lock-up on its own pale
  background — so setting the name over the image put white type across the middle of it. The
  tile is a panel instead: the artwork gets a well of its own at the top, and the name and the
  count sit below it on a solid band, ruled off, where nothing can collide with them.

  With no artwork the well carries the admin-chosen icon on the dark ground, so a shop that
  has not uploaded logos still reads as a set of tiles rather than as empty boxes.
--}}
<a href="{{ route('category.show', $category) }}" class="group panel panel-hover relative flex flex-col overflow-hidden">
  <div class="well h-[104px]">
    @if ($category->image)
      <img src="{{ Storage::disk('public')->url($category->image) }}" alt="" loading="lazy"
        class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
    @else
      <span class="absolute inset-0 hero-ground"></span>
      <span class="absolute -right-8 -top-8 w-28 h-28 rounded-full border border-white/10 spin-slow"></span>
      <span class="zoom-in-hover relative grid place-items-center w-12 h-12 rounded-[var(--radius-control)] bg-white/10 border border-white/20 text-white">
        <x-store.icon :name="$category->icon_key" class="w-6 h-6" /></span>
    @endif
  </div>

  <div class="rule flex items-center gap-2 px-3.5 py-3">
    <span class="min-w-0 flex-1">
      <span
        class="block font-display text-[14px] font-bold leading-tight text-ink-900 truncate transition group-hover:text-brand-700">{{ $category->t('name') }}</span>
      @if ($count !== null)
        <span class="tnum block mt-0.5 text-[11.5px] text-ink-500">{{ __('shop.products_count', ['count' => $count]) }}</span>
      @endif
    </span>
    <x-store.ui name="arrow-right" class="w-4 h-4 shrink-0 text-brand-600 icon-nudge" />
  </div>
</a>
