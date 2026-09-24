@php
  $home = app(\App\Settings\HomepageSettings::class);
@endphp

<x-layouts.storefront
  :seo-title="$page->t('seo_title') ?: $page->t('title')"
  :seo-description="$page->t('seo_description') ?: $home->story_title"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="$page->t('title')" :crumbs="[$page->title => null]" padding="pb-24" />
  </x-slot:hero>

  <div class="wrap">
    @if ($stats->isNotEmpty())
      <div class="stagger grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach ($stats as $stat)
          <div class="reveal panel panel-hover p-5 text-center" style="--i: {{ $loop->index }}">
            <p class="tnum count-up font-display text-[2rem] font-extrabold text-ink-900 leading-none">{{ $stat->t('value') }}</p>
            <p class="mt-2 text-sm text-ink-500">{{ $stat->t('label') }}</p>
          </div>
        @endforeach
      </div>
    @endif

    <section class="mt-14 grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
      <div class="reveal reveal-left slab noise aspect-[4/3] grid place-items-center">
        <span class="aurora"></span>
        <span class="relative font-display text-6xl md:text-7xl font-bold tracking-[-0.03em] float">{{ $home->story_badge }}</span>
        <span class="absolute inset-8 rounded-full border border-white/10 spin-slow"></span>
      </div>
      <div class="reveal reveal-right">
        <x-store.section-head :label="$home->story_eyebrow" :title="$home->story_title" />
        <p class="lede mt-4">{{ $home->story_body }}</p>
      </div>
    </section>

    @if (filled($page->t('body')))
      <section class="reveal panel mt-14 p-6 md:p-10 prose-store">{!! clean($page->t('body')) !!}</section>
    @endif

    @if ($team->isNotEmpty())
      <section class="mt-16">
        <x-store.section-head :label="$home->team_eyebrow" :title="$home->team_title" align="center" />
        <div class="stagger mt-9 grid grid-cols-2 md:grid-cols-4 gap-5">
          @foreach ($team as $member)
            <div class="reveal group panel panel-hover p-5 text-center" style="--i: {{ $loop->index }}">
              {{-- A face is the one genuinely circular thing on the site, so this is the one
                   place a full radius is right. --}}
              <span class="well mx-auto w-24 h-24 rounded-full border border-ink-100">
                @if ($member->photo)
                  <img src="{{ Storage::disk('public')->url($member->photo) }}" alt="{{ $member->name }}" loading="lazy"
                    class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
                @else
                  <span class="font-display text-2xl font-bold text-ink-400">{{ $member->initials() }}</span>
                @endif
              </span>
              <h3 class="mt-4 font-display font-bold text-ink-900">{{ $member->name }}</h3>
              <p class="text-sm text-ink-500">{{ $member->t('role') }}</p>

              <div class="mt-3 flex justify-center gap-2">
                @foreach ($member->socials ?? [] as $network => $url)
                  @if (filled($url))
                    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $network }}"
                      class="tap grid place-items-center w-8 h-8 rounded-[var(--radius-control)] bg-ink-50 text-ink-500 hover:bg-brand-600 hover:text-white transition">
                      <x-store.ui :name="$network === 'email' ? 'mail' : $network" class="w-3.5 h-3.5" /></a>
                  @endif
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </section>
    @endif
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
