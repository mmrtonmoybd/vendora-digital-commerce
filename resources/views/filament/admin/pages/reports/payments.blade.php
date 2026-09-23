@php
  use App\Support\Money;
  $data = $this->reportData();
  $g = $data['gateway'];
  $d = $data['deposits'];
@endphp

<x-filament-panels::page>
  <form wire:submit.prevent class="fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    {{ $this->periodForm }}
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Showing {{ $this->periodLabel() }}</p>
  </form>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-report.stat label="Gateway attempts" :value="number_format($g['attempts'])" />
    <x-report.stat label="Succeeded" :value="number_format($g['paid'])" color="success" />
    <x-report.stat label="Success rate" :value="$g['success_rate'] . '%'" :color="$g['success_rate'] < 80 ? 'danger' : 'success'" />
    <x-report.stat label="Average time to verify" :value="$g['avg_verify_seconds'] . 's'" />
    <x-report.stat label="Failed" :value="number_format($g['failed'])" :color="$g['failed'] > 0 ? 'danger' : 'primary'" />
    <x-report.stat label="Cancelled" :value="number_format($g['cancelled'])" />
    <x-report.stat label="Expired unverified" :value="number_format($g['expired'])" :color="$g['expired'] > 0 ? 'warning' : 'primary'" />
  </div>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-report.stat label="Deposits completed" :value="number_format($d['completed'])" color="success" />
    <x-report.stat label="Deposit value" :value="Money::format($d['completed_value'])" />
    <x-report.stat label="Deposit fees kept" :value="Money::format($d['fees'])" />
    <x-report.stat label="Average approval time" :value="$d['avg_minutes'] . ' min'" hint="Submitted to processed" />
    <x-report.stat label="Pending" :value="number_format($d['pending'])" :color="$d['pending'] > 0 ? 'warning' : 'success'" />
    <x-report.stat label="Rejected" :value="number_format($d['rejected'])" :color="$d['rejected'] > 0 ? 'danger' : 'primary'" />
  </div>

  <x-report.table title="Revenue by payment method" :headings="['Method' => 'left', 'Orders' => 'right', 'Revenue' => 'right']">
    @forelse ($data['methods'] as $row)
      <tr>
        <td class="px-5 py-2.5 text-gray-950 dark:text-white">{{ $row->name }}</td>
        <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->orders) }}</td>
        <td class="px-5 py-2.5 text-right font-medium">{{ Money::format((int) $row->revenue) }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="3" class="px-5 py-8 text-center text-gray-500">No payments in this period.</td>
      </tr>
    @endforelse
  </x-report.table>
</x-filament-panels::page>
