@props(['title', 'headings' => [], 'empty' => 'Nothing in this period.'])

<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr>
                    @foreach ($headings as $heading => $align)
                        <th class="px-5 py-3 text-{{ $align }} font-medium text-gray-500 dark:text-gray-400">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
