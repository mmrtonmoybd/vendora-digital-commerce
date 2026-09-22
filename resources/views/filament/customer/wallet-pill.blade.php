@php
    $user = auth()->user();
@endphp

<div class="flex items-center gap-2">
    <span class="hidden items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1.5 text-sm font-semibold text-primary-700 sm:inline-flex dark:bg-primary-500/10 dark:text-primary-400">
        <x-filament::icon icon="heroicon-o-wallet" class="size-4" />
        {{ \App\Support\Money::format((int) $user->balance) }}
    </span>

    <a
        href="{{ \Filament\Facades\Filament::getPanel('customer')->getUrl() }}/deposits/create"
        class="fi-btn fi-btn-size-sm fi-color-primary inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-500"
    >
        Deposit
    </a>
</div>
