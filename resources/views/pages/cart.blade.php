<x-layouts.storefront seo-title="My Cart" seo-robots="noindex,follow" main-class="-mt-4 relative z-10">
<x-slot:hero>
    <x-store.page-hero title="My Cart" :crumbs="['Cart' => null]" />
</x-slot:hero>

<livewire:cart-page />
<div class="h-20"></div>
</x-layouts.storefront>
