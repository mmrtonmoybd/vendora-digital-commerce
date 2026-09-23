@php
  $home = app(\App\Settings\HomepageSettings::class);
  $general = $store->general();
  $tiles = $store->tileCategories();
  $featureIcons = [
      '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
',
      '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
      '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
  ];
@endphp

<x-layouts.storefront :seo-title="$general->site_name . ' — ' . $store->st($general, 'tagline')" :seo-description="$store->st($general, 'tagline')">
  <x-slot:hero>
    <div class="pb-24 md:pb-32">
      <div class="grid lg:grid-cols-[1.1fr_1fr] gap-10 items-center pt-6 md:pt-12">
        <div>
          <span class="inline-flex items-center gap-2 pl-1.5 pr-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20 text-sm">
            <span class="flex -space-x-1.5"><span class="w-6 h-6 rounded-full ring-2 ring-brand-700 bg-amber-300"></span><span
                class="w-6 h-6 rounded-full ring-2 ring-brand-700 bg-rose-300"></span><span
                class="w-6 h-6 rounded-full ring-2 ring-brand-700 bg-white"></span></span>
            {{ $store->st($home, 'hero_badge') }}</span>
          <h1 class="mt-5 font-display text-4xl sm:text-5xl lg:text-[3.6rem] font-extrabold leading-[1.05] tracking-tight">
            {{ $store->st($home, 'hero_title_before') }}<br><span
              class="font-script font-normal text-amber-300 text-[1.15em]">{{ $store->st($home, 'hero_title_script') }}</span>
            {{ $store->st($home, 'hero_title_after') }}</h1>
          <p class="mt-5 text-lg text-white/85 max-w-xl">{{ $store->st($home, 'hero_subtitle') }}</p>
          <ul class="mt-6 flex flex-wrap gap-2">
            @foreach ($store->stLines($home, 'hero_features') as $index => $feature)
              <li class="inline-flex items-center gap-2 h-9 px-3.5 rounded-full bg-white/10 ring-1 ring-white/15 text-sm font-medium"><svg
                  class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  stroke-linejoin="round" aria-hidden="true">{!! $featureIcons[$index % count($featureIcons)] !!}</svg> {{ $feature }}</li>
            @endforeach
          </ul>
          <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ $home->hero_primary_url ?: '#categories' }}"
              class="inline-flex items-center gap-2 h-12 px-6 rounded-full bg-white text-brand-700 font-bold hover:bg-brand-50 shadow-xl shadow-brand-950/20">{{ $store->st($home, 'hero_primary_text') }}
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <path d="M5 12h14" />
                <path d="m12 5 7 7-7 7" />
              </svg></a>
            <a href="{{ $home->hero_secondary_url ?: $store->supportTarget()['url'] }}"
              class="inline-flex items-center gap-2 h-12 px-6 rounded-full ring-1 ring-white/40 hover:bg-white/10 font-semibold"><svg class="w-4 h-4"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" />
              </svg> {{ $store->st($home, 'hero_secondary_text') }}</a>
          </div>
        </div>
        <!-- slider -->
        <div x-data="{ i: 0, n: {{ max(1, $slides->count()) }}, t: null, go(k) { this.i = (k + this.n) % this.n }, init() { if (this.n > 1) this.t = setInterval(() => this.go(this.i + 1), {{ $store->theme()->hero_autoplay_seconds * 1000 }}) } }" class="relative">
          <div class="absolute inset-0 translate-x-2 translate-y-3 md:translate-x-4 md:translate-y-4 rounded-3xl bg-white/10 ring-1 ring-white/15 rotate-2">
          </div>
          <div class="relative h-[300px] md:h-[340px]">
            @foreach ($slides as $index => $slide)
              <div x-show="i==={{ $index }}" x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 translate-x-6"
                class="absolute inset-0 rounded-3xl bg-gradient-to-br {{ $slide->gradient }} {{ $slide->text_class }} p-7 md:p-9 flex flex-col justify-between overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-56 h-56 rounded-full bg-brand-600/10"></div>
                <div class="absolute right-6 top-6 grid place-items-center w-20 h-20 rounded-2xl bg-brand-600 text-white rotate-6 shadow-xl"><x-store.icon
                    :name="$slide->icon_key" stroke-width="1.8" class="w-9 h-9" /></div>
                <span
                  class="inline-flex w-fit px-3 py-1 rounded-full bg-brand-600 text-white text-xs font-bold uppercase tracking-wider">{{ $slide->t('eyebrow') }}</span>
                <div>
                  <h3 class="font-display text-2xl md:text-3xl font-bold leading-tight max-w-[14ch]">{{ $slide->t('title') }}</h3>
                  <p class="mt-2 text-sm opacity-75">{{ $slide->t('subtitle') }}</p>
                  @if (filled($slide->t('button_text')))
                    <a href="{{ $slide->button_url ?: '#categories' }}"
                      class="mt-5 inline-flex items-center gap-2 h-10 px-5 rounded-full bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700">{{ $slide->t('button_text') }}
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                      </svg></a>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
          <div class="relative mt-5 flex items-center justify-center gap-2">
            <template x-for="k in n"><button @click="go(k-1)" class="h-2 rounded-full transition-all" :class="i === k - 1 ? 'w-8 bg-white' : 'w-2 bg-white/40'"
                :aria-label="'Slide ' + k"></button></template>
          </div>
        </div>
      </div>
    </div>
  </x-slot:hero>

  <section id="categories" class="-mt-10 md:-mt-16 relative z-10">
    <div class="max-w-7xl mx-auto px-4">
      <div class="bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-4 md:p-6">
        <div class="flex items-end justify-between mb-4">
          <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">{{ $store->st($home, 'categories_eyebrow') }}</p>
            <h2 class="font-display text-xl md:text-2xl font-bold">{{ $store->st($home, 'categories_title') }}</h2>
          </div>
          <a href="{{ route('search') }}"
            class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">{{ __('shop.all_products') }} <svg
              class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              aria-hidden="true">
              <path d="M5 12h14" />
              <path d="m12 5 7 7-7 7" />
            </svg></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
          @foreach ($tiles as $tile)
            <x-store.category-tile :category="$tile" />
          @endforeach
        </div>
      </div>
    </div>
  </section>

  @foreach ($homeCategories as $category)
    <section id="cat-{{ $category->slug }}" class="scroll-mt-24 pt-12">
      <div class="max-w-7xl mx-auto px-4 space-y-5">
        <x-store.category-bar :category="$category" :count="$category->active_products_count" />
        <div class="grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
          @foreach ($category->activeProducts as $product)
            <x-store.product-card :product="$product" />
          @endforeach
        </div>
      </div>
    </section>
  @endforeach

  @if ($testimonials->isNotEmpty())
    <!-- testimonials -->
    <section class="pt-20">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto">
          <p class="text-xs font-bold uppercase tracking-widest text-brand-600">{{ $store->st($home, 'testimonials_eyebrow') }}</p>
          <h2 class="mt-2 font-display text-3xl md:text-4xl font-bold">{{ $store->st($home, 'testimonials_title') }}</h2>
          <p class="mt-3 text-stone-600">{{ $store->st($home, 'testimonials_subtitle') }}</p>
        </div>
        <div class="mt-10 grid md:grid-cols-3 gap-6">
          @foreach ($testimonials as $testimonial)
            <figure class="relative bg-white rounded-2xl p-6 shadow-card ring-1 ring-black/5">
              <span class="absolute -top-4 right-6 font-script text-6xl text-brand-200 leading-none select-none">“</span>
              <div class="flex gap-0.5">
                @for ($i = 0; $i < $testimonial->rating; $i++)
                  <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                  </svg>
                @endfor
              </div>
              <blockquote class="mt-3 text-[15px] leading-relaxed text-stone-700">{{ $testimonial->t('quote') }}</blockquote>
              <figcaption class="mt-5 flex items-center gap-3"><span
                  class="grid place-items-center w-10 h-10 rounded-full bg-brand-100 text-brand-700 font-bold text-sm">{{ $testimonial->initials() }}</span>
                <span><b class="block text-sm">{{ $testimonial->name }}</b><span class="text-xs text-stone-500">{{ $testimonial->t('role') }}</span></span>
              </figcaption>
            </figure>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @if ($faqs->isNotEmpty())
    <!-- FAQ -->
    <section id="faq" class="pt-20 scroll-mt-24">
      <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-[1fr_1.4fr] gap-10">
        <div>
          <p class="text-xs font-bold uppercase tracking-widest text-brand-600">{{ $store->st($home, 'faq_eyebrow') }}</p>
          <h2 class="mt-2 font-display text-3xl md:text-4xl font-bold">{{ $store->st($home, 'faq_title') }}</h2>
          <p class="mt-3 text-stone-600">{{ $store->st($home, 'faq_subtitle') }}</p>
          <a href="{{ route('contact') }}"
            class="mt-6 inline-flex items-center gap-2 h-11 px-5 rounded-full bg-ink text-white font-semibold hover:bg-stone-800"><svg class="w-4 h-4"
              viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              aria-hidden="true">
              <path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3" />
            </svg> {{ $store->st($home, 'faq_button_text') }}</a>
        </div>
        <div class="space-y-3">
          @foreach ($faqs as $index => $faq)
            <div class="bg-white rounded-2xl ring-1 ring-black/5 shadow-card" x-data="{ o: {{ $index === 0 ? 'true' : 'false' }} }">
              <button @click="o=!o" class="w-full flex items-center justify-between gap-4 p-5 text-left font-display font-semibold"
                :aria-expanded="o">{{ $faq->t('question') }}
                <span class="grid place-items-center w-8 h-8 rounded-full shrink-0 transition"
                  :class="o ? 'bg-brand-600 text-white rotate-180' : 'bg-brand-50 text-brand-700'"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6" />
                  </svg></span></button>
              <div x-show="o" x-transition class="px-5 pb-5 -mt-1 text-stone-600 leading-relaxed">{{ $faq->t('answer') }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <!-- CTA -->
  <section class="py-20">
    <div class="max-w-7xl mx-auto px-4">
      <div class="relative overflow-hidden rounded-[2rem] hero-ground text-white p-8 md:p-14 grid md:grid-cols-[1.5fr_1fr] gap-8 items-center">
        <svg class="absolute right-0 bottom-0 w-2/3 h-full text-white/10" viewBox="0 0 400 200" preserveAspectRatio="none" aria-hidden="true">
          <path fill="currentColor" d="M0,200 C120,120 200,180 400,60 L400,200 Z" />
        </svg>
        <div class="relative">
          <h2 class="font-display text-3xl md:text-4xl font-bold leading-tight">{{ $store->st($home, 'cta_title') }}</h2>
          <p class="mt-3 text-white/85 max-w-lg">{{ $store->st($home, 'cta_subtitle') }}</p>
        </div>
        <div class="relative flex flex-wrap gap-3 md:justify-end">
          <a href="{{ $home->cta_primary_url }}"
            class="inline-flex items-center gap-2 h-12 px-6 rounded-full bg-white text-brand-700 font-bold hover:bg-brand-50">{{ $store->st($home, 'cta_primary_text') }}
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              aria-hidden="true">
              <path d="M5 12h14" />
              <path d="m12 5 7 7-7 7" />
            </svg></a>
          <a href="{{ $home->cta_secondary_url }}"
            class="inline-flex items-center h-12 px-6 rounded-full ring-1 ring-white/40 font-semibold hover:bg-white/10">{{ $store->st($home, 'cta_secondary_text') }}</a>
        </div>
      </div>
    </div>
  </section>
</x-layouts.storefront>
