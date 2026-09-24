{{-- A CMS page: whatever an administrator wrote, in the shop's type. The measure is the
     narrow wrap rather than the full grid, because terms and policies are read line by line
     and a 4xl column of body copy is too wide to track. --}}
<x-layouts.storefront
  :seo-title="$page->t('seo_title') ?: $page->t('title')"
  :seo-description="$page->t('seo_description')"
  seo-type="article"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="$page->t('title')" :crumbs="[$page->title => null]" padding="pb-24" />
  </x-slot:hero>

  <div class="wrap-narrow">
    <article class="reveal panel p-6 md:p-10 prose-store">{!! clean($page->t('body')) !!}</article>
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
