@props(['label', 'value', 'hint' => null, 'color' => 'primary'])

<div class="fi-wi-stats-overview-stat rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
    <p @class([
        'mt-1 text-2xl font-semibold tracking-tight',
        'text-gray-950 dark:text-white' => $color === 'primary',
        'text-success-600 dark:text-success-400' => $color === 'success',
        'text-danger-600 dark:text-danger-400' => $color === 'danger',
        'text-warning-600 dark:text-warning-400' => $color === 'warning',
    ])>{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
