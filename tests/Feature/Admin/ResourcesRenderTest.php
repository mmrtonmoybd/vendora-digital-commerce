<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Admin;
use Database\Seeders\DemoParitySeeder;
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

/**
 * Every Filament page class must point at a resource that exists.
 *
 * Three resources were written as a folder of page classes whose Resource file was never
 * created — orphans that registered no routes and no navigation, so the feature behind them
 * (menus, traffic sources) simply was not reachable. Nothing in the suite noticed, because
 * an unregistered resource has no index page to render.
 */
it('has no page class pointing at a resource that does not exist', function (): void {
    $orphans = [];

    foreach (glob(app_path('Filament/*/Resources/*/Pages/*.php')) as $file) {
        $class = str($file)
            ->after(app_path().DIRECTORY_SEPARATOR)
            ->before('.php')
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->prepend('App\\')
            ->toString();

        if (! class_exists($class)) {
            $orphans[] = $class.' (page class will not load)';

            continue;
        }

        $resource = (new ReflectionClass($class))->getStaticPropertyValue('resource', null);

        if ($resource === null || ! class_exists($resource)) {
            $orphans[] = class_basename($class).' → '.($resource ?? 'no resource').' is missing';
        }
    }

    expect($orphans)->toBe([]);
});

/**
 * Open a real record in every resource that has a view or edit page.
 *
 * An index page renders the table and nothing else, so a broken infolist or form — a
 * reference to a class that does not exist in this Filament version, say — stays invisible
 * until a human clicks a row. This clicks one.
 */
it('opens a record in every resource that has a view or edit page', function (): void {
    // Without data this test passes by skipping everything, which is worse than not having
    // it. The parity seeder is the same catalogue the storefront tests use.
    $this->seed(DemoParitySeeder::class);

    $failures = [];

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        $record = $resource::getModel()::query()->first();

        if ($record === null) {
            continue;
        }

        foreach (['view', 'edit'] as $page) {
            if (! $resource::hasPage($page)) {
                continue;
            }

            $response = $this->get($resource::getUrl($page, ['record' => $record]));

            if ($response->getStatusCode() !== 200) {
                $failures[] = class_basename($resource).' '.$page.' → '.$response->getStatusCode();
            }
        }
    }

    expect($failures)->toBe([]);
});
