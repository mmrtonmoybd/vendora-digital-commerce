<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Admin;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Admin::create([
        'name' => 'Render Admin',
        'email' => 'render@test.local',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $admin->assignRole('super_admin');
    $this->actingAs($admin, 'admin');
});

/**
 * Every registered resource's index page must render.
 *
 * Driven off the panel's own registry rather than a hand-written list, so a resource added
 * later is covered without anyone remembering to add it here.
 */
it('renders every resource index page', function (): void {
    $failures = [];

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        if (! $resource::hasPage('index')) {
            continue;
        }

        $response = $this->get($resource::getUrl('index'));

        if ($response->getStatusCode() !== 200) {
            $failures[] = class_basename($resource).' → '.$response->getStatusCode();
        }
    }

    expect($failures)->toBe([]);
});

it('renders every registered page', function (): void {
    $failures = [];

    foreach (Filament::getPanel('admin')->getPages() as $page) {
        $response = $this->get($page::getUrl());

        if ($response->getStatusCode() !== 200) {
            $failures[] = class_basename($page).' → '.$response->getStatusCode();
        }
    }

    expect($failures)->toBe([]);
});
