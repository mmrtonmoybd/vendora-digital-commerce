<x-layouts.storefront seo-title="Checkout" seo-robots="noindex,nofollow" main-class="-mt-4 relative z-10">
<x-slot:hero>
    <x-store.page-hero title="Checkout" :crumbs="['Cart' => route('cart'), 'Checkout' => null]" />
</x-slot:hero>

<livewire:checkout-page />
<div class="h-20"></div>
</x-layouts.storefront>
