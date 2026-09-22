<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets\Concerns;

use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Query\Builder;

/**
 * Shared plumbing for the summary widgets that sit above a resource table.
 *
 * Every figure is read from the page's own filtered, searched and sorted query, so the
 * summary always describes exactly the rows the operator is looking at rather than the
 * whole table — which is the difference between a useful number and a decorative one.
 */
trait SummarisesTable
{
    /**
     * The page query reduced to its filters: ordering, eager loads and the table's own
     * select list are all dropped.
     *
     * The select list matters. A resource table selects `table`.* plus any withCount
     * subqueries, and appending an aggregate to that is illegal under MySQL's
     * ONLY_FULL_GROUP_BY. Callers supply their own columns instead.
     */
    protected function summaryQuery(): Builder
    {
        $query = $this->getPageTableQuery()->reorder()->toBase();
        $query->columns = null;

        return $query;
    }

    /**
     * Run one aggregate query for every figure the widget needs.
     *
     * @param  array<string, string>  $expressions  alias => SQL aggregate
     * @return array<string, string>
     */
    protected function summarise(array $expressions): array
    {
        $select = collect($expressions)
            ->map(fn (string $expression, string $alias): string => "{$expression} as {$alias}")
            ->implode(', ');

        $row = (array) ($this->summaryQuery()->selectRaw($select)->first() ?? []);

        return collect($expressions)
            ->map(fn (string $expression, string $alias): string => (string) ($row[$alias] ?? '0'))
            ->all();
    }

    /** Aggregates come back as strings from PDO; money is still paisa at this point. */
    protected function money(string|int|float|null $paisa): string
    {
        return Money::format((int) $paisa);
    }

    protected function quantity(string|int|float|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals);
    }

    protected function summaryStat(string $label, string $value, Heroicon $icon, string $color, ?string $description = null): Stat
    {
        return Stat::make($label, $value)
            ->icon($icon)
            ->color($color)
            ->when($description !== null, fn (Stat $stat): Stat => $stat->description($description));
    }
}
