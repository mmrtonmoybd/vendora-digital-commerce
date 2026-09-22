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

final class PaymentReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|null|UnitEnum $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Payments & gateway';

    protected string $view = 'filament.admin.pages.reports.payments';

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
            'methods' => $reports->paymentMethodMix($from, $to),
            'gateway' => $reports->gatewayHealth($from, $to),
            'deposits' => $reports->depositSummary($from, $to),
        ];
    }
}
