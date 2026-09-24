@php
  $home = app(\App\Settings\HomepageSettings::class);
  $general = $store->general();
  $tiles = $store->tileCategories();
@endphp

<x-layouts.storefront :seo-title="$general->site_name . ' — ' . $store->st($general, 'tagline')" :seo-description="$store->st($general, 'tagline')">
  {{--
    The billboard.

    The slides are artwork an operator uploads and points at a category or a product, so the
    banner is the promotion — there is no headline written over it here to fight with what
    the image already says. Clicking anywhere on a slide follows the link that was set with
    it.

    A slide without artwork still has to work, so it falls back to the shop's own dark
    ground carrying the slide's words and its button.
  --}}
  @if ($slides->isNotEmpty())
    @php
      $lead = $slides->first();
      $secondary = $slides->slice(1)->take(2)->values();
      $autoplay = $store->theme()->hero_autoplay_seconds * 1000;
    @endphp

    <section class="pt-5 md:pt-6">
      <div class="wrap">
        <div class="rise-in"
          x-data="{
              i: 0,
              n: {{ $slides->count() }},
              timer: null,
              go(k) { this.i = (k + this.n) % this.n },
              start() { if (this.n > 1) this.timer = setInterval(() => this.go(this.i + 1), {{ $autoplay }}) },
              stop() { clearInterval(this.timer) },
              init() { this.start() }
          }"
          @mouseenter="stop()" @mouseleave="start()" @keydown.left.window="go(i - 1)" @keydown.right.window="go(i + 1)">

          <div
            class="group relative overflow-hidden rounded-[var(--radius-panel)] border border-navy-200 bg-navy-900
            aspect-[16/10] min-[560px]:aspect-[21/9] md:aspect-[1200/400]">
            @foreach ($slides as $index => $slide)
              <a href="{{ $slide->button_url ?: '#categories' }}" x-show="i==={{ $index }}" x-transition:enter="transition duration-700"
                x-transition:enter-start="opacity-0" x-transition:leave="transition duration-500" x-transition:leave-end="opacity-0"
                class="absolute inset-0 block" @if ($index > 0) x-cloak @endif
                aria-label="{{ $slide->t('title') ?: $slide->t('eyebrow') }}">
                @if ($slide->image)
                  <img src="{{ Storage::disk('public')->url($slide->image) }}" alt="{{ $slide->t('title') }}"
                    @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                    class="absolute inset-0 w-full h-full object-fill">
                @else
                  {{-- No artwork uploaded: the slide still has something to say. --}}
                  <span class="hero-ground absolute inset-0"></span>
                  <span class="aurora"></span>
                  <span class="relative h-full flex flex-col justify-center gap-3 px-7 md:px-14 max-w-2xl text-white">
                    @if (filled($slide->t('eyebrow')))
                      <span class="label-line flex items-center gap-2 text-brand-400"><span
                          class="live-dot live-dot-brand"></span>{{ $slide->t('eyebrow') }}</span>
                    @endif
                    <span class="font-display text-[1.75rem] md:text-[2.75rem] font-bold leading-[1.08] tracking-[-0.025em]">{{ $slide->t('title') }}</span>
                    @if (filled($slide->t('subtitle')))
                      <span class="text-white/70 max-w-[52ch] leading-relaxed">{{ $slide->t('subtitle') }}</span>
                    @endif
                    @if (filled($slide->t('button_text')))
                      <span class="btn btn-primary mt-2 w-fit sheen">{{ $slide->t('button_text') }}<x-store.ui name="arrow-right" class="w-4 h-4" /></span>
                    @endif
                  </span>
                @endif
              </a>
            @endforeach

            {{-- Controls only exist when there is somewhere else to go. --}}
            <template x-if="n > 1">
              <div>
                <button type="button" @click.prevent="go(i - 1)" aria-label="{{ __('store.previous') }}"
                  class="tap absolute left-3 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full bg-navy-950/45 text-white backdrop-blur-sm
                    opacity-0 group-hover:opacity-100 focus-visible:opacity-100 transition hover:bg-navy-950/70">
                  <x-store.ui name="arrow-left" class="w-4 h-4" /></button>
                <button type="button" @click.prevent="go(i + 1)" aria-label="{{ __('store.next') }}"
                  class="tap absolute right-3 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full bg-navy-950/45 text-white backdrop-blur-sm
                    opacity-0 group-hover:opacity-100 focus-visible:opacity-100 transition hover:bg-navy-950/70">
                  <x-store.ui name="arrow-right" class="w-4 h-4" /></button>

                <div class="absolute bottom-3.5 left-1/2 -translate-x-1/2 flex items-center gap-2">
                  <template x-for="k in n"><button type="button" @click.prevent="go(k-1)" class="h-1.5 rounded-full transition-all duration-300"
                      :class="i === k - 1 ? 'w-7 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/80'" :aria-label="'Slide ' + k"></button></template>
                </div>
              </div>
            </template>
          </div>

          @if ($secondary->isNotEmpty())
            {{-- The banners under the billboard: the same slides at a size that lets two of
                 them sit side by side, each still pointing where it was pointed. --}}
            <div class="mt-4 grid gap-4 {{ $secondary->count() > 1 ? 'sm:grid-cols-2' : '' }}">
              @foreach ($secondary as $slide)
                <a href="{{ $slide->button_url ?: '#categories' }}"
                  class="group relative overflow-hidden rounded-[var(--radius-panel)] border border-navy-200 bg-navy-900 aspect-[1200/400] panel-hover"
                  aria-label="{{ $slide->t('title') ?: $slide->t('eyebrow') }}">
                  @if ($slide->image)
                    <img src="{{ Storage::disk('public')->url($slide->image) }}" alt="{{ $slide->t('title') }}" loading="lazy"
                      class="zoom-in-hover absolute inset-0 w-full h-full object-fill">
                  @else
                    <span class="hero-ground absolute inset-0"></span>
                    <span class="relative h-full flex flex-col justify-center gap-1.5 px-6 text-white">
                      <span class="font-display text-lg font-semibold leading-tight">{{ $slide->t('title') }}</span>
                      <span class="text-sm text-white/65 line-clamp-2">{{ $slide->t('subtitle') }}</span>
                    </span>
                  @endif
                </a>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </section>
  @endif

  {{-- What the shop sells, by category. --}}
  <section id="categories" class="pt-10 md:pt-12 scroll-mt-24">
    <div class="wrap">
      <div class="panel p-4 md:p-6">
        <x-store.section-head :label="$store->st($home, 'categories_eyebrow')" :title="$store->st($home, 'categories_title')" :href="route('search')" :link-text="__('shop.all_products')" class="mb-5" />
        <div class="reveal stagger grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
          @foreach ($tiles as $tile)
            <x-store.category-tile :category="$tile" :count="$tile->active_products_count ?? null" />
          @endforeach
        </div>
      </div>
    </div>
  </section>

  @foreach ($homeCategories as $category)
    <section id="cat-{{ $category->slug }}" class="scroll-mt-24 pt-14">
      <div class="wrap space-y-6">
        <x-store.section-head :label="__('shop.products_count', ['count' => $category->active_products_count])" :title="$category->sectionHeading()" :href="route('category.show', $category)" />
        <div class="reveal stagger grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-5">
          @foreach ($category->activeProducts as $product)
            <x-store.product-card :product="$product" />
          @endforeach
        </div>
      </div>
    </section>
  @endforeach

  @if ($testimonials->isNotEmpty())
    <section class="section">
      <div class="wrap">
        <x-store.section-head :label="$store->st($home, 'testimonials_eyebrow')" :title="$store->st($home, 'testimonials_title')" :lead="$store->st($home, 'testimonials_subtitle')" align="center" />
        <div class="reveal stagger mt-10 grid md:grid-cols-3 gap-5">
          @foreach ($testimonials as $testimonial)
            <figure class="panel panel-hover p-6 flex flex-col">
              <div class="flex gap-0.5 text-brand-600">
                @for ($i = 0; $i < $testimonial->rating; $i++)
                  <x-store.ui name="star" class="w-4 h-4 fill-current" stroke-width="1" />
                @endfor
              </div>
              <blockquote class="mt-3 text-[15px] leading-relaxed text-navy-600 flex-1">{{ $testimonial->t('quote') }}</blockquote>
              <figcaption class="mt-5 pt-5 rule flex items-center gap-3">
                <span
                  class="grid place-items-center w-10 h-10 rounded-full bg-navy-900 text-white font-display font-semibold text-sm">{{ $testimonial->initials() }}</span>
                <span class="min-w-0"><b class="block text-sm text-navy-900 truncate">{{ $testimonial->name }}</b><span
                    class="text-xs text-navy-500">{{ $testimonial->t('role') }}</span></span>
              </figcaption>
            </figure>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @if ($faqs->isNotEmpty())
    <section id="faq" class="section-tight scroll-mt-24">
      <div class="wrap grid lg:grid-cols-[1fr_1.4fr] gap-10 lg:gap-14 items-start">
        <div class="reveal reveal-left lg:sticky lg:top-8">
          <x-store.section-head :label="$store->st($home, 'faq_eyebrow')" :title="$store->st($home, 'faq_title')" :lead="$store->st($home, 'faq_subtitle')" />
          <a href="{{ route('contact') }}" class="btn btn-dark mt-6 group"><x-store.ui name="headset"
              class="w-4 h-4" />{{ $store->st($home, 'faq_button_text') }}<x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
        </div>
        <div class="reveal stagger space-y-3">
          @foreach ($faqs as $index => $faq)
            <div class="panel overflow-hidden" x-data="{ o: {{ $index === 0 ? 'true' : 'false' }} }" :class="o && 'border-navy-300'">
              <button @click="o=!o" class="w-full flex items-center justify-between gap-4 p-5 text-left font-display font-semibold text-navy-900"
                :aria-expanded="o">{{ $faq->t('question') }}
                <span class="grid place-items-center w-8 h-8 rounded-full shrink-0 transition duration-300"
                  :class="o ? 'bg-brand-600 text-white rotate-180' : 'bg-navy-100 text-navy-600'"><x-store.ui name="chevron-down" class="w-4 h-4" /></span>
              </button>
              <div x-show="o" x-collapse x-cloak>
                <p class="px-5 pb-5 -mt-1 text-navy-600 leading-relaxed">{{ $faq->t('answer') }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <section class="section">
    <div class="wrap">
      <div class="reveal reveal-zoom slab noise p-8 md:p-14 grid md:grid-cols-[1.5fr_1fr] gap-8 items-center">
        <span class="aurora"></span>
        <div class="relative">
          <h2 class="font-display text-3xl md:text-[2.5rem] font-bold leading-[1.1] tracking-[-0.02em]">{{ $store->st($home, 'cta_title') }}</h2>
          <p class="mt-3 text-white/70 max-w-lg leading-relaxed">{{ $store->st($home, 'cta_subtitle') }}</p>
        </div>
        <div class="relative flex flex-wrap gap-3 md:justify-end">
          <a href="{{ $home->cta_primary_url }}" class="group btn btn-lg bg-white text-navy-900 hover:bg-navy-100 sheen">
            {{ $store->st($home, 'cta_primary_text') }}<x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
          <a href="{{ $home->cta_secondary_url }}" class="btn btn-onDark btn-lg">{{ $store->st($home, 'cta_secondary_text') }}</a>
        </div>
      </div>
    </div>
  </section>
</x-layouts.storefront>
