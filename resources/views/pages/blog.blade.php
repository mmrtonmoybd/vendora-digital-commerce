<x-layouts.storefront
    seo-title="Blog"
    seo-description="Guides, reviews and news about digital products."
    main-class="-mt-4 relative z-10"
>
<x-slot:hero>
    <x-store.page-hero title="Blog" lead="Guides, reviews and news from our team." :crumbs="['Blog' => null]" />
</x-slot:hero>

  <div class="max-w-7xl mx-auto px-4">
    <div class="flex gap-2 overflow-x-auto no-scrollbar"><a href="{{ route('blog.index') }}" class="shrink-0 h-10 px-4 inline-flex items-center rounded-full text-sm font-semibold {{ $activeCategory === '' ? 'bg-brand-600 text-white' : 'bg-white ring-1 ring-stone-200' }}">All</a>@foreach ($categories as $category)<a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="shrink-0 h-10 px-4 inline-flex items-center rounded-full text-sm font-semibold {{ $activeCategory === $category->slug ? 'bg-brand-600 text-white' : 'bg-white ring-1 ring-stone-200' }}">{{ $category->name }}</a>@endforeach</div>
    <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">@forelse ($posts as $index => $post)<article class="group bg-white rounded-2xl overflow-hidden shadow-card shadow-card-hover ring-1 ring-black/5 flex flex-col">
      <a href="{{ route('blog.show', $post) }}" class="block aspect-[16/9] bg-gradient-to-br {{ $post->gradient }} art-rings relative">@if ($post->cover)<img src="{{ Storage::disk('public')->url($post->cover) }}" alt="{{ $post->title }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">@endif<span class="absolute bottom-3 left-3 px-2.5 py-1 rounded-full bg-white text-ink text-xs font-bold">{{ $post->blogCategory?->name }}</span>
        <span class="absolute top-3 right-4 font-display text-6xl font-extrabold text-white/20">{{ str_pad((string) ($posts->firstItem() + $index), 2, '0', STR_PAD_LEFT) }}</span></a>
      <div class="p-5 flex-1 flex flex-col"><p class="text-xs text-stone-500 flex items-center gap-2"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> {{ $post->read_minutes }} min read · {{ $post->published_at?->format('M j, Y') }}</p>
        <h3 class="mt-2 font-display font-semibold text-lg leading-snug group-hover:text-brand-700"><a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></h3>
        <p class="mt-2 text-sm text-stone-600 line-clamp-2">{{ $post->excerpt }}</p>
        <a href="{{ route('blog.show', $post) }}" class="mt-auto pt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">Read more <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a></div>
    </article>@empty
    <div class="col-span-full py-20 text-center">
      <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-brand-50 text-brand-600"><svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg></span>
      <h2 class="mt-4 font-display text-xl font-bold">Nothing published yet</h2><p class="text-stone-600">Check back soon — we are writing.</p>
    </div>
    @endforelse</div>
    {{ $posts->links('vendor.pagination.storefront') }}
  </div>
<div class="h-20"></div>
</x-layouts.storefront>
