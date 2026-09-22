<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Reports;

use App\Filament\Admin\Pages\Reports\Concerns\HasReportPeriod;
use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class SourceReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|null|UnitEnum $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Traffic sources';

    protected string $view = 'filament.admin.pages.reports.sources';

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('view_reports') ?? false;
    }

    public function periodForm(Schema $schema): Schema
    {
        return $schema->statePath('period')->columns(3)->components($this->periodFields());
    }

    /** @return array<string, mixed> */
    public function reportData(): array
    {
        $reports = app(ReportService::class);
        $from = $this->from();
        $to = $this->until();

        return [
            'sources' => $reports->sourcePerformance($from, $to),
        ];
    }
}
