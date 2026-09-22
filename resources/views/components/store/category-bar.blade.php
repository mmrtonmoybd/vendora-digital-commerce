@props(['category', 'count' => 0])

      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r {{ $category->gradient }} text-white px-5 md:px-7 py-4 flex items-center gap-4 shadow-lg shadow-brand-900/10">
      <div class="absolute inset-0 opacity-20" style="background:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0) 0 0/18px 18px"></div>
      <span class="relative grid place-items-center w-11 h-11 rounded-xl bg-white/20 ring-1 ring-white/30"><x-store.icon :name="$category->icon_key" class="w-5 h-5" /></span>
      <div class="relative"><h2 class="font-display text-lg md:text-2xl font-bold leading-tight">{{ $category->sectionHeading() }}</h2><p class="text-xs md:text-sm text-white/80">{{ $count }} {{ \Illuminate\Support\Str::plural('product', $count) }} · instant delivery</p></div>
      <a href="{{ route('category.show', $category) }}" class="relative ml-auto inline-flex items-center gap-1.5 h-9 px-4 rounded-full bg-white/15 hover:bg-white/25 text-sm font-semibold whitespace-nowrap">View all <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
    </div>
