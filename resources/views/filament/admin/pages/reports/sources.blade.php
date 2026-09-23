@php
  use App\Support\Money;
  $data = $this->reportData();
@endphp

<x-filament-panels::page>
  <form wire:submit.prevent class="fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    {{ $this->periodForm }}
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Showing {{ $this->periodLabel() }}</p>
  </form>

  <x-report.table title="Where the money came from" :headings="['Source' => 'left', 'Orders' => 'right', 'Average order' => 'right', 'Revenue' => 'right']">
    @forelse ($data['sources'] as $row)
      <tr>
        <td class="px-5 py-2.5 text-gray-950 dark:text-white">{{ $row->name }}</td>
        <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->orders) }}</td>
        <td class="px-5 py-2.5 text-right">{{ Money::format((int) round((float) $row->average_order)) }}</td>
        <td class="px-5 py-2.5 text-right font-medium">{{ Money::format((int) $row->revenue) }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="4" class="px-5 py-8 text-center text-gray-500">No attributed sales in this period.</td>
      </tr>
    @endforelse
  </x-report.table>
</x-filament-panels::page>
