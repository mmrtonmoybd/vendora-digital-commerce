@props(['category'])

      <a href="{{ route('category.show', $category) }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $category->gradient }} text-white p-4 md:p-5 min-h-[128px] flex flex-col justify-between shadow-md hover:-translate-y-1 hover:shadow-xl transition">
        <span class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-white/10 group-hover:scale-125 transition duration-500"></span>
        <span class="relative grid place-items-center w-11 h-11 rounded-xl bg-white/20 ring-1 ring-white/30"><x-store.icon :name="$category->icon_key" class="w-5 h-5" /></span>
        <span class="relative flex items-end justify-between gap-2"><span class="font-display font-semibold leading-tight">{{ $category->name }}</span><svg class="w-4 h-4 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></span>
      </a>
