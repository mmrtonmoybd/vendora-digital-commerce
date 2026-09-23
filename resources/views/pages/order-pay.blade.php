<x-layouts.storefront
  :seo-title="'Order ' . $order->order_number"
  seo-robots="noindex,nofollow"
  main-class="-mt-20 md:-mt-28 relative z-10">
  <x-slot:hero>
    <x-store.page-hero title="Your order" :crumbs="['Order' => null]" padding="pb-28 md:pb-36" />
  </x-slot:hero>

  @if ($order->isPaid())
    <div data-track="purchase" data-track-payload='@json(app(\App\Services\TrackingService::class)->purchase($order))'></div>
  @endif

  @if (session('status'))
    <div class="max-w-2xl mx-auto px-4 mb-4">
      <p class="p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-semibold">{{ session('status') }}</p>
    </div>
  @endif
  @if (session('error'))
    <div class="max-w-2xl mx-auto px-4 mb-4">
      <p class="p-3 rounded-xl bg-red-50 text-red-700 text-sm font-semibold">{{ session('error') }}</p>
    </div>
  @endif

  <livewire:order-pay-page :order="$order" />
  <div class="h-20"></div>
</x-layouts.storefront>
