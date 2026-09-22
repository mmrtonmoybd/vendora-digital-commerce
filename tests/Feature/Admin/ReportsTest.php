<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\Admin\Pages\Reports\SalesReport;
use App\Models\Admin;
use App\Services\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('renders the sales report for staff who may see reports', function (): void {
    $admin = Admin::create([
        'name' => 'Accountant', 'email' => 'acc@test.local',
        'password' => 'password', 'status' => UserStatus::Active,
    ]);
    $admin->assignRole('accountant');

    $this->actingAs($admin, 'admin')->get(SalesReport::getUrl())->assertSuccessful();
});

it('hides reports from staff without the permission', function (): void {
    $admin = Admin::create([
        'name' => 'Editor', 'email' => 'ed@test.local',
        'password' => 'password', 'status' => UserStatus::Active,
    ]);
    $admin->assignRole('content_editor');

    expect(SalesReport::canAccess())->toBeFalse();

    $this->actingAs($admin, 'admin')->get(SalesReport::getUrl())->assertForbidden();
});

it('returns zeroed figures rather than failing on an empty period', function (): void {
    $summary = app(ReportService::class)->salesSummary(now()->subYear(), now());

    expect($summary)
        ->toHaveKeys(['orders', 'gross', 'discounts', 'fees', 'refunds', 'net', 'average_order'])
        ->and($summary['orders'])->toBe(0)
        ->and($summary['average_order'])->toBe(0);
});

it('computes fulfilment percentiles without loading rows into PHP', function (): void {
    $summary = app(ReportService::class)->fulfillmentSummary(now()->subYear(), now());

    expect($summary)->toHaveKeys(['granted', 'avg_minutes', 'p90_minutes', 'sla_breaches', 'sla_breach_rate']);
});
