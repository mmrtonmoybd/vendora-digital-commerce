<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\Admin\Widgets\BusinessStats;
use App\Filament\Admin\Widgets\LatestOrders;
use App\Filament\Admin\Widgets\OrderStatusChart;
use App\Filament\Admin\Widgets\RevenueChart;
use App\Filament\Admin\Widgets\SourcePerformanceChart;
use App\Models\Admin;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Admin::create([
        'name' => 'Dash Admin',
        'email' => 'dash@test.local',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $admin->assignRole('super_admin');
    $this->actingAs($admin, 'admin');
});

it('renders the dashboard', function (): void {
    $this->get('/admin')->assertSuccessful();
});

it('renders every dashboard widget', function (string $widget): void {
    livewire($widget)->assertSuccessful();
})->with([
    BusinessStats::class,
    RevenueChart::class,
    OrderStatusChart::class,
    SourcePerformanceChart::class,
    LatestOrders::class,
]);
