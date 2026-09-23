@php
  use App\Support\Money;
  $data = $this->reportData();
  $s = $data['summary'];
@endphp

<x-filament-panels::page>
  <form wire:submit.prevent class="fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    {{ $this->periodForm }}
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Showing {{ $this->periodLabel() }}</p>
  </form>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-report.stat label="New customers" :value="number_format($s['new_customers'])" color="success" />
    <x-report.stat label="Customers who bought" :value="number_format($s['buyers'])" />
    <x-report.stat label="Bought more than once" :value="number_format($s['repeat_buyers'])" />
    <x-report.stat label="Repeat rate" :value="$s['repeat_rate'] . '%'" :color="$s['repeat_rate'] > 20 ? 'success' : 'primary'" />
    <x-report.stat label="Wallet liability" :value="Money::format($s['wallet_liability'])" color="warning" hint="Money owed to customers today" />
  </div>

  <x-report.table title="Top customers" :headings="['Customer' => 'left', 'Orders' => 'right', 'Spent' => 'right']">
    @forelse ($data['top'] as $row)
      <tr>
        <td class="px-5 py-2.5 text-gray-950 dark:text-white">{{ $row->name }}<span class="block text-xs text-gray-500">{{ $row->email }}</span></td>
        <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->orders) }}</td>
        <td class="px-5 py-2.5 text-right font-medium">{{ Money::format((int) $row->spent) }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="3" class="px-5 py-8 text-center text-gray-500">No purchases in this period.</td>
      </tr>
    @endforelse
  </x-report.table>
</x-filament-panels::page>
