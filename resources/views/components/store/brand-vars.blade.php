{{-- The brand scale, printed from settings so an admin can re-skin the shop without a
     rebuild. The template reads these same --brand-* triplets. --}}
<style>:root{
@foreach (\App\Support\BrandPalette::fromHex($brandHex) as $shade => $rgb)--brand-{{ $shade }}: {{ $rgb }};
@endforeach}</style>
