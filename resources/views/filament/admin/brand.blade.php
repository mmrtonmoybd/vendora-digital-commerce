@php
  $settings = app(\App\Settings\GeneralSettings::class);
  $logo = $settings->logo;
@endphp

<div class="flex items-center gap-2">
  @if ($logo)
    <img
      src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}"
      alt="{{ $settings->site_name }}"
      class="h-9 w-auto">
  @else
    <span class="grid size-9 place-items-center rounded-xl bg-primary-600 font-bold text-white">
      {{ \Illuminate\Support\Str::of($settings->site_name)->substr(0, 1)->upper() }}
    </span>
    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $settings->site_name }}</span>
  @endif
</div>
