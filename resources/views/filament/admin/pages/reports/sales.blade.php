@php
    use App\Support\Money;

    $data = $this->reportData();
    $summary = $data['summary'];
@endphp

<x-filament-panels::page>
    <form wire:submit.prevent class="fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        {{ $this->periodForm }}
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Showing {{ $this->periodLabel() }}</p>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-report.stat label="Net revenue" :value="Money::format($summary['net'])" color="success" hint="After discounts and refunds" />
        <x-report.stat label="Orders" :value="number_format($summary['orders'])" />
        <x-report.stat label="Average order" :value="Money::format($summary['average_order'])" />
        <x-report.stat label="Refunded" :value="Money::format($summary['refunds'])" :color="$summary['refunds'] > 0 ? 'danger' : 'primary'" />
        <x-report.stat label="Gross sales" :value="Money::format($summary['gross'])" hint="Before discounts and fees" />
        <x-report.stat label="Discounts given" :value="Money::format($summary['discounts'])" color="warning" />
        <x-report.stat label="Processing fees" :value="Money::format($summary['fees'])" />
        <x-report.stat
            label="Refund rate"
            :value="$summary['gross'] > 0 ? round($summary['refunds'] / $summary['gross'] * 100, 1).'%' : '0%'"
        />
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-report.table title="Revenue over time" :headings="['Period' => 'left', 'Orders' => 'right', 'Revenue' => 'right']">
            @forelse ($data['overTime'] as $row)
                <tr>
                    <td class="px-5 py-2.5 text-gray-950 dark:text-white">{{ $row->bucket }}</td>
                    <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->orders) }}</td>
                    <td class="px-5 py-2.5 text-right font-medium">{{ Money::format((int) $row->revenue) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500">No sales in this period.</td></tr>
            @endforelse
        </x-report.table>

        <x-report.table title="By category" :headings="['Category' => 'left', 'Units' => 'right', 'Revenue' => 'right']">
            @forelse ($data['categories'] as $row)
                <tr>
                    <td class="px-5 py-2.5 text-gray-950 dark:text-white">{{ $row->name }}</td>
                    <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->quantity) }}</td>
                    <td class="px-5 py-2.5 text-right font-medium">{{ Money::format((int) $row->revenue) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500">No sales in this period.</td></tr>
            @endforelse
        </x-report.table>

        <x-report.table title="By payment method" :headings="['Method' => 'left', 'Orders' => 'right', 'Revenue' => 'right']">
            @forelse ($data['methods'] as $row)
                <tr>
                    <td class="px-5 py-2.5 text-gray-950 dark:text-white">{{ $row->name }}</td>
                    <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->orders) }}</td>
                    <td class="px-5 py-2.5 text-right font-medium">{{ Money::format((int) $row->revenue) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500">No sales in this period.</td></tr>
            @endforelse
        </x-report.table>

        <x-report.table title="Coupons" :headings="['Code' => 'left', 'Uses' => 'right', 'Discount given' => 'right']">
            @forelse ($data['coupons'] as $row)
                <tr>
                    <td class="px-5 py-2.5 font-mono text-gray-950 dark:text-white">{{ $row->code }}</td>
                    <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->uses) }}</td>
                    <td class="px-5 py-2.5 text-right font-medium">{{ Money::format((int) $row->discount_given) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500">No coupons used in this period.</td></tr>
            @endforelse
        </x-report.table>
    </div>
</x-filament-panels::page>
