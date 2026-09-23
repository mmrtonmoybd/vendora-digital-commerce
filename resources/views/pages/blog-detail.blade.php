@php
    $shareUrl = rawurlencode(route('blog.show', $post));
    $shareText = rawurlencode($post->t('title'));

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
    main-class="-mt-4 relative z-10"
>
<x-slot:hero>
    <x-store.page-hero :title="$post->t('title')" :crumbs="['Blog' => route('blog.index'), $post->blogCategory?->name => null]" />
</x-slot:hero>

  <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-[1fr_320px] gap-8 items-start">
    <article class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 overflow-hidden">
      <div class="aspect-[21/9] bg-gradient-to-br {{ $post->gradient }} art-rings">@if ($post->cover)<img src="{{ Storage::disk('public')->url($post->cover) }}" alt="{{ $post->t('title') }}" class="w-full h-full object-cover">@endif</div>
      <div class="p-6 md:p-10 prose prose-stone prose-lg max-w-none prose-headings:font-display prose-a:text-brand-700">
        @if (filled($post->t('excerpt')))<p class="lead">{{ $post->t('excerpt') }}</p>@endif
        {!! clean($post->t('body')) !!}
      </div>
      <div class="px-6 md:px-10 pb-8 flex flex-wrap items-center gap-2 text-sm"><span class="font-semibold mr-2">{{ __('content.share') }}</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" class="grid place-items-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 hover:bg-brand-600 hover:text-white" aria-label="Facebook"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></a><a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener" class="grid place-items-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 hover:bg-brand-600 hover:text-white" aria-label="WhatsApp"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></a><a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" class="grid place-items-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 hover:bg-brand-600 hover:text-white" aria-label="Telegram"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></a><button type="button" @click="window.copyText(@js(route('blog.show', $post)))" class="grid place-items-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 hover:bg-brand-600 hover:text-white" aria-label="{{ __('store.copy_link') }}"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg></button></div>
    </article>
    <aside class="space-y-6 lg:sticky lg:top-6">
      @if ($related->isNotEmpty())
      <div class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-5"><h3 class="font-display font-semibold">{{ __('content.recent_posts') }}</h3>
        <ul class="mt-3 space-y-3">@foreach ($related as $item)<li><a href="{{ route('blog.show', $item) }}" class="flex gap-3 group"><span class="shrink-0 w-16 h-16 rounded-xl bg-gradient-to-br {{ $item->gradient }}"></span><span class="text-sm font-semibold leading-snug group-hover:text-brand-700">{{ $item->title }}</span></a></li>@endforeach</ul></div>
      @endif
      <div class="rounded-3xl hero-ground text-white p-6"><h3 class="font-display text-xl font-bold">{{ app(\App\Settings\HomepageSettings::class)->cta_title }}</h3><p class="mt-1 text-sm text-white/85">{{ app(\App\Settings\HomepageSettings::class)->cta_subtitle }}</p><a href="{{ route('home') }}#categories" class="mt-4 inline-flex h-10 px-5 items-center rounded-full bg-white text-brand-700 font-semibold text-sm">{{ __('store.shop_now') }}</a></div>
    </aside>
  </div>
<div class="h-20"></div>
</x-layouts.storefront>
