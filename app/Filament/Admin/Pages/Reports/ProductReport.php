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

final class ProductReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|null|UnitEnum $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Products';

    protected string $view = 'filament.admin.pages.reports.products';

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
            'products' => $reports->productPerformance($from, $to),
            'categories' => $reports->categoryPerformance($from, $to),
        ];
    }
}
