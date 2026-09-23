@php
  $data = $this->reportData();
  $s = $data['summary'];
@endphp

<x-filament-panels::page>
  <form wire:submit.prevent class="fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    {{ $this->periodForm }}
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Showing {{ $this->periodLabel() }}</p>
  </form>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-report.stat label="Access granted" :value="number_format($s['granted'])" color="success" />
    <x-report.stat label="Average time to grant" :value="$s['avg_minutes'] . ' min'" hint="From payment to invitation" />
    <x-report.stat label="90th percentile" :value="$s['p90_minutes'] . ' min'" hint="9 in 10 were faster than this" />
    <x-report.stat label="Waiting right now" :value="number_format($s['awaiting_now'])" :color="$s['awaiting_now'] > 0 ? 'warning' : 'success'" />
    <x-report.stat label="SLA breaches" :value="number_format($s['sla_breaches'])" :color="$s['sla_breaches'] > 0 ? 'danger' : 'success'" />
    <x-report.stat label="Breach rate" :value="$s['sla_breach_rate'] . '%'" :color="$s['sla_breach_rate'] > 10 ? 'danger' : 'primary'" />
    <x-report.stat label="Problems reported" :value="number_format($s['problem_reports'])" :color="$s['problem_reports'] > 0 ? 'warning' : 'success'" />
  </div>

  <x-report.table title="Per staff member" :headings="['Staff' => 'left', 'Granted' => 'right', 'Average minutes' => 'right']">
    @forelse ($data['byAdmin'] as $row)
      <tr>
        <td class="px-5 py-2.5 text-gray-950 dark:text-white">{{ $row->name }}</td>
        <td class="px-5 py-2.5 text-right">{{ number_format((int) $row->granted) }}</td>
        <td class="px-5 py-2.5 text-right">{{ round((float) $row->avg_minutes) }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="3" class="px-5 py-8 text-center text-gray-500">Nothing granted in this period.</td>
      </tr>
    @endforelse
  </x-report.table>
</x-filament-panels::page>
