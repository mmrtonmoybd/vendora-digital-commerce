{{-- A CMS page. The template's "How to buy" demo puts four step cards above the article;
     those are content, so an admin writes them into the page body rather than having them
     hard-coded here where only one page could ever use them. --}}
<x-layouts.storefront
  :seo-title="$page->t('seo_title') ?: $page->t('title')"
  :seo-description="$page->t('seo_description')"
  seo-type="article"
  main-class="-mt-4 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="$page->t('title')" :crumbs="[$page->title => null]" />
  </x-slot:hero>

  <div class="max-w-5xl mx-auto px-4">
    <article
      class="mt-8 bg-white rounded-3xl shadow-card ring-1 ring-black/5 p-6 md:p-10 prose prose-stone max-w-none prose-headings:font-display prose-a:text-brand-700 prose-strong:text-ink">
      {!! clean($page->t('body')) !!}
    </article>
  </div>
  <div class="h-20"></div>
</x-layouts.storefront>
