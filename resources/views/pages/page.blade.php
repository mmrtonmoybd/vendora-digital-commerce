{{-- A CMS page: whatever an administrator wrote, in the shop's type. --}}
<x-layouts.storefront
  :seo-title="$page->t('seo_title') ?: $page->t('title')"
  :seo-description="$page->t('seo_description')"
  seo-type="article"
  main-class="-mt-8 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="$page->t('title')" :crumbs="[$page->title => null]" padding="pb-24" />
  </x-slot:hero>

  <div class="max-w-4xl mx-auto px-4">
    <article class="reveal panel p-6 md:p-10 prose-store">{!! clean($page->t('body')) !!}</article>
  </div>

  <div class="h-20"></div>
</x-layouts.storefront>
