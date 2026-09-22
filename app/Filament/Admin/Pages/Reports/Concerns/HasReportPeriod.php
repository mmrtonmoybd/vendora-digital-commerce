<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Reports\Concerns;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;

/**
 * The period selector every report shares.
 *
 * Presets cover what people actually ask for; "custom" reveals the two date fields. The
 * period lives in Livewire state so changing it re-renders the figures without a page
 * load, and the resolved dates are what the ReportService caches against.
 */
trait HasReportPeriod
{
    /** @var array<string, mixed> */
    public array $period = ['preset' => '30', 'from' => null, 'until' => null];

    /** @return array<string, string> */
    public static function presets(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'this_year' => 'This year',
            'custom' => 'Custom range',
        ];
    }

    /** @return array<int, mixed> */
    protected function periodFields(): array
    {
        return [
            Select::make('preset')
                ->label('Period')
                ->options(self::presets())
                ->default('30')
                ->live()
                ->native(false)
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    if ($state !== 'custom') {
                        $set('from', null);
                        $set('until', null);
                    }
                }),
            DatePicker::make('from')->native(false)->live()->visible(fn (Get $get): bool => $get('preset') === 'custom'),
            DatePicker::make('until')->native(false)->live()->visible(fn (Get $get): bool => $get('preset') === 'custom'),
        ];
    }

    public function from(): Carbon
    {
        return match ($this->period['preset'] ?? '30') {
            'today' => today()->startOfDay(),
            'yesterday' => today()->subDay()->startOfDay(),
            'this_month' => today()->startOfMonth(),
            'last_month' => today()->subMonthNoOverflow()->startOfMonth(),
            'this_year' => today()->startOfYear(),
            'custom' => $this->period['from'] ? Carbon::parse($this->period['from'])->startOfDay() : today()->subDays(30),
            default => today()->subDays((int) ($this->period['preset'] ?? 30))->startOfDay(),
        };
    }

    public function until(): Carbon
    {
        return match ($this->period['preset'] ?? '30') {
            'yesterday' => today()->subDay()->endOfDay(),
            'last_month' => today()->subMonthNoOverflow()->endOfMonth(),
            'custom' => $this->period['until'] ? Carbon::parse($this->period['until'])->endOfDay() : now(),
            default => now(),
        };
    }

    public function periodLabel(): string
    {
        return $this->from()->toFormattedDateString().' — '.$this->until()->toFormattedDateString();
    }

    /** Long periods are charted by month; anything shorter by day. */
    public function isMonthly(): bool
    {
        return $this->from()->diffInDays($this->until()) > 120;
    }
}
