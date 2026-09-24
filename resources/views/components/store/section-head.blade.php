@props(['title', 'label' => null, 'lead' => null, 'href' => null, 'linkText' => null, 'align' => 'left'])

{{--
  A section heading.

  One shape for every band on the site: an optional pointer line, the heading, an optional
  lead, and the link out to the full list on the right. The pointer is .label-line — sentence
  case, brand ink, with a rule running out of it — rather than a tracked-out all-caps eyebrow:
  it is there to say what kind of thing follows, not to decorate the heading.

  The live dot in front of the pointer is not ornament either. Seats move and grants land
  while somebody is reading, and the dot says the figures under this heading are current.
--}}
<div
  {{ $attributes->merge(['class' => 'reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between ' . ($align === 'center' ? 'sm:flex-col sm:items-center text-center' : '')]) }}>
  <div class="{{ $align === 'center' ? 'max-w-2xl mx-auto' : '' }}">
    @if ($label)
      <p class="label-line {{ $align === 'center' ? 'justify-center' : '' }}">
        <span class="live-dot live-dot-brand shrink-0"></span>{{ $label }}
      </p>
    @endif
    <h2 class="h-section {{ $label ? 'mt-2.5' : '' }}">{{ $title }}</h2>
    @if ($lead)
      <p class="lede mt-2.5 {{ $align === 'center' ? 'mx-auto' : '' }}">{{ $lead }}</p>
    @endif
  </div>

  @if ($href)
    <a href="{{ $href }}" class="group link inline-flex items-center gap-1.5 shrink-0">{{ $linkText ?: __('store.view_all') }}
      <x-store.ui name="arrow-right" class="w-4 h-4 icon-nudge" /></a>
  @endif
</div>
