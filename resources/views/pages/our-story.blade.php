@php
    $home = app(\App\Settings\HomepageSettings::class);
    $socialIcons = [
        'facebook' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'email' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
    ];
@endphp

<x-layouts.storefront
    :seo-title="$page->seo_title ?: $page->title"
    :seo-description="$page->seo_description ?: $home->story_title"
    main-class="-mt-4 relative z-10"
>
<x-slot:hero>
    <x-store.page-hero :title="$page->title" :crumbs="[$page->title => null]" />
</x-slot:hero>

  <div class="max-w-7xl mx-auto px-4">
    @if ($stats->isNotEmpty())<div class="grid grid-cols-2 md:grid-cols-4 gap-4">@foreach ($stats as $stat)<div class="bg-white rounded-2xl p-5 text-center shadow-card ring-1 ring-black/5"><p class="font-display text-3xl font-extrabold text-brand-700">{{ $stat->value }}</p><p class="text-sm text-stone-600">{{ $stat->label }}</p></div>@endforeach</div>@endif
    <section class="mt-14 grid lg:grid-cols-2 gap-10 items-center">
      <div class="relative aspect-[4/3] rounded-3xl hero-ground overflow-hidden"><span class="absolute inset-0 grid place-items-center font-script text-7xl md:text-8xl text-white/90">{{ $home->story_badge }}</span></div>
      <div><p class="text-xs font-bold uppercase tracking-widest text-brand-600">{{ $home->story_eyebrow }}</p><h2 class="mt-2 font-display text-3xl md:text-4xl font-bold">{{ $home->story_title }}</h2>
        <p class="mt-4 text-stone-600 leading-relaxed">{{ $home->story_body }}</p></div>
    </section>
    @if (filled($page->body))
    <section class="mt-14 bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-6 md:p-10 prose prose-stone max-w-none prose-headings:font-display prose-a:text-brand-700">{!! clean($page->body) !!}</section>
    @endif
    @if ($team->isNotEmpty())
    <section class="mt-16"><div class="text-center"><p class="text-xs font-bold uppercase tracking-widest text-brand-600">{{ $home->team_eyebrow }}</p><h2 class="mt-2 font-display text-3xl font-bold">{{ $home->team_title }}</h2></div>
      <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-5">
        @foreach ($team as $member)<div class="group bg-white rounded-2xl p-5 text-center shadow-card ring-1 ring-black/5"><div class="mx-auto w-24 h-24 rounded-full p-1 bg-gradient-to-br from-brand-500 to-brand-800"><div class="w-full h-full rounded-full bg-brand-50 grid place-items-center font-display text-2xl font-bold text-brand-700">@if ($member->photo)<img src="{{ Storage::disk('public')->url($member->photo) }}" alt="{{ $member->name }}" class="w-full h-full rounded-full object-cover">@else{{ $member->initials() }}@endif</div></div><h3 class="mt-4 font-display font-semibold">{{ $member->name }}</h3><p class="text-sm text-stone-500">{{ $member->role }}</p><div class="mt-3 flex justify-center gap-2 opacity-60 group-hover:opacity-100">@foreach (($member->socials ?? []) as $network => $url)@if (filled($url) && isset($socialIcons[$network]))<a href="{{ $url }}" target="_blank" rel="noopener" class="grid place-items-center w-8 h-8 rounded-full bg-brand-50 text-brand-700" aria-label="{{ $network }}"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $socialIcons[$network] !!}</svg></a>@endif @endforeach</div></div>@endforeach
      </div></section>
    @endif
  </div>
<div class="h-20"></div>
</x-layouts.storefront>
