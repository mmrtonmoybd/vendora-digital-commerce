@php
  $shareUrl = rawurlencode(route('blog.show', $post));
  $shareText = rawurlencode($post->t('title'));

  $shares = [
      ['facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $shareUrl],
      ['whatsapp', 'https://wa.me/?text=' . $shareText . '%20' . $shareUrl],
      ['telegram', 'https://t.me/share/url?url=' . $shareUrl . '&text=' . $shareText],
  ];

  $schema = array_filter([
      '@type' => 'BlogPosting',
      'headline' => $post->t('title'),
      'description' => $post->t('excerpt'),
      'datePublished' => $post->published_at?->toIso8601String(),
      'dateModified' => $post->updated_at?->toIso8601String(),
      'image' => $post->cover ? Storage::disk('public')->url($post->cover) : null,
      'author' => ['@type' => 'Organization', 'name' => $store->general()->site_name],
      'mainEntityOfPage' => route('blog.show', $post),
  ]);
@endphp

<x-layouts.storefront
  :seo-title="$post->t('seo_title') ?: $post->t('title')"
  :seo-description="$post->t('seo_description') ?: $post->t('excerpt')"
  :seo-image="$post->cover ? Storage::disk('public')->url($post->cover) : null"
  seo-type="article"
  :seo-schema="$schema"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="$post->t('title')" :crumbs="[__('content.blog') => route('blog.index'), $post->blogCategory?->name => null]" padding="pb-24" />
  </x-slot:hero>

  <div class="wrap grid lg:grid-cols-[1fr_320px] gap-8 items-start">
    <article class="panel overflow-hidden">
      @if ($post->cover)
        <div class="well aspect-[21/9] border-b border-navy-100">
          <img src="{{ Storage::disk('public')->url($post->cover) }}" alt="{{ $post->t('title') }}" class="absolute inset-0 w-full h-full object-fill">
        </div>
      @endif

      <div class="p-6 md:p-10">
        <p class="tnum flex items-center gap-2 text-sm text-navy-500"><x-store.ui name="clock" class="w-4 h-4 text-navy-400" />
          {{ __('content.min_read', ['count' => $post->read_minutes]) }} · {{ $post->published_at?->format(\App\Support\Dates::DATE) }}</p>

        <div class="prose-store mt-5">
          @if (filled($post->t('excerpt')))
            <p class="lead">{{ $post->t('excerpt') }}</p>
          @endif
          {!! clean($post->t('body')) !!}
        </div>

        <div class="mt-8 pt-6 rule flex flex-wrap items-center gap-2">
          <span class="text-sm font-semibold text-navy-700 mr-2">{{ __('content.share') }}</span>
          @foreach ($shares as [$network, $url])
            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"
              class="tap grid place-items-center w-9 h-9 rounded-full bg-navy-50 text-navy-600 hover:bg-brand-600 hover:text-white transition">
              <x-store.ui :name="$network" class="w-4 h-4" /></a>
          @endforeach
          <button type="button" @click="window.copyText(@js(route('blog.show', $post)))" aria-label="{{ __('store.copy_link') }}"
            class="tap grid place-items-center w-9 h-9 rounded-full bg-navy-50 text-navy-600 hover:bg-brand-600 hover:text-white transition">
            <x-store.ui name="copy" class="w-4 h-4" /></button>
        </div>
      </div>
    </article>

    <aside class="space-y-5 lg:sticky lg:top-6">
      @if ($related->isNotEmpty())
        <div class="panel panel-p">
          <h2 class="h-panel">{{ __('content.recent_posts') }}</h2>
          <ul class="mt-4 space-y-4">
            @foreach ($related as $item)
              <li><a href="{{ route('blog.show', $item) }}" class="group flex gap-3">
                  <span class="well shrink-0 w-16 h-16 rounded-[var(--radius-control)] border border-navy-100">
                    @if ($item->cover)
                      <img src="{{ Storage::disk('public')->url($item->cover) }}" alt="" loading="lazy"
                        class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
                    @else
                      <x-store.ui name="book" class="w-5 h-5 text-navy-300" />
                    @endif
                  </span>
                  <span class="text-sm font-semibold leading-snug text-navy-900 group-hover:text-brand-700 transition">{{ $item->title }}</span></a></li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="slab noise p-6">
        <span class="aurora"></span>
        <div class="relative">
          <h2 class="font-display text-xl font-bold tracking-[-0.02em]">{{ app(\App\Settings\HomepageSettings::class)->cta_title }}</h2>
          <p class="mt-1.5 text-sm text-white/70 leading-relaxed">{{ app(\App\Settings\HomepageSettings::class)->cta_subtitle }}</p>
          <a href="{{ route('home') }}#categories" class="group btn bg-white text-navy-900 hover:bg-navy-100 mt-5 sheen">{{ __('store.shop_now') }}
            <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
        </div>
      </div>
    </aside>
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
