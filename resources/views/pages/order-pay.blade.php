{{-- The order page. The receipt itself is a Livewire component because the manual-transfer
     form posts back into it; everything here is the frame around it. The breadcrumb names
     the order number rather than the word "order", because that is what the customer is
     looking for when they come back to this page from an email. --}}
<x-layouts.storefront
  :seo-title="'Order ' . $order->order_number"
  seo-robots="noindex,nofollow"
  main-class="-mt-20 md:-mt-28 relative z-10">
  <x-slot:hero>
    <x-store.page-hero :title="__('account.your_order')" :crumbs="[$order->order_number => null]" padding="pb-28 md:pb-36" />
  </x-slot:hero>

  @if ($order->isPaid())
    <div data-track="purchase" data-track-payload='@json(app(\App\Services\TrackingService::class)->purchase($order))'></div>
  @endif

  @if (session('status'))
    <div class="wrap-narrow mb-4">
      <p class="note note-ok flex items-start gap-2 font-semibold">
        <x-store.ui name="check-circle" class="w-4 h-4 mt-0.5 shrink-0" />{{ session('status') }}
      </p>
    </div>
  @endif
  @if (session('error'))
    <div class="wrap-narrow mb-4">
      <p class="note note-bad flex items-start gap-2 font-semibold">
        <x-store.ui name="alert" class="w-4 h-4 mt-0.5 shrink-0" />{{ session('error') }}
      </p>
    </div>
  @endif

  <livewire:order-pay-page :order="$order" />
  <div class="h-20"></div>
</x-layouts.storefront>
