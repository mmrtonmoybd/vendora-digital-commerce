<x-layouts.storefront
  :seo-title="__('content.blog')"
  :seo-description="__('content.blog_lead')"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="__('content.blog')" :lead="__('content.blog_lead')" :crumbs="[__('content.blog') => null]" padding="pb-24" />
  </x-slot:hero>

  <div class="wrap">
    {{-- Topics run off the edge rather than wrapping, so a shop writing about a dozen
         platforms does not push the first article below the fold on a phone. --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
      <a href="{{ route('blog.index') }}" class="chip {{ $activeCategory === '' ? 'chip-on' : '' }}">{{ __('content.all') }}</a>
      @foreach ($categories as $category)
        <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
          class="chip {{ $activeCategory === $category->slug ? 'chip-on' : '' }}">{{ $category->t('name') }}</a>
      @endforeach
    </div>

    <div class="stagger mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @forelse ($posts as $post)
        <article class="reveal group panel panel-hover edge-glow overflow-hidden flex flex-col" style="--i: {{ $loop->index }}">
          <a href="{{ route('blog.show', $post) }}" class="well block aspect-[16/9] border-b border-ink-100">
            @if ($post->cover)
              <img src="{{ Storage::disk('public')->url($post->cover) }}" alt="{{ $post->t('title') }}" loading="lazy"
                class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
            @else
              <x-store.ui name="book" class="w-10 h-10 text-ink-300" />
            @endif
            @if ($post->blogCategory)
              <span class="flag absolute bottom-3 left-3 bg-ink-950/80 text-white backdrop-blur-sm">{{ $post->blogCategory->t('name') }}</span>
            @endif
          </a>

          <div class="p-5 flex-1 flex flex-col">
            <h2 class="font-display text-lg font-bold leading-snug text-ink-900 group-hover:text-brand-700 transition">
              <a href="{{ route('blog.show', $post) }}">{{ $post->t('title') }}</a>
            </h2>

            <p class="mt-2 text-sm text-ink-500 line-clamp-2 leading-relaxed">{{ $post->t('excerpt') }}</p>

            {{-- When it went out and how long it takes are the two things a reader weighs
                 before committing, so they are ruled as terms rather than run together into
                 a byline. --}}
            <dl class="mt-4 pt-2 rule-dashed text-[12px]">
              <div class="kv py-0.5">
                <dt>{{ __('content.published') }}</dt>
                <dd class="tnum">{{ $post->published_at?->format(\App\Support\Dates::DATE) }}</dd>
              </div>
              <div class="kv py-0.5">
                <dt>{{ __('content.read_time') }}</dt>
                <dd class="tnum">{{ __('content.minutes', ['count' => $post->read_minutes]) }}</dd>
              </div>
            </dl>

            <a href="{{ route('blog.show', $post) }}" class="link mt-auto pt-4 inline-flex items-center gap-1.5">{{ __('content.read_more') }}
              <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
          </div>
        </article>
      @empty
        <div class="reveal col-span-full panel py-16 px-6 text-center">
          <span class="inline-grid place-items-center w-16 h-16 rounded-[var(--radius-panel)] bg-ink-50 text-ink-400 float">
            <x-store.ui name="book" class="w-7 h-7" /></span>
          <h2 class="mt-4 h-section">{{ __('content.nothing_published') }}</h2>
          <p class="lede mt-1.5 mx-auto">{{ __('content.check_back_soon') }}</p>
        </div>
      @endforelse
    </div>

    {{ $posts->links('vendor.pagination.storefront') }}
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
