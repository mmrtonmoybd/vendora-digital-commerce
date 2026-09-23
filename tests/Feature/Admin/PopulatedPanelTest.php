<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Support\Dates;
use Database\Seeders\DemoActivitySeeder;
use Database\Seeders\DemoParitySeeder;
use Database\Seeders\OrderSourceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;

/**
 * Every admin screen, rendered with a shop's worth of real rows behind it.
 *
 * The existing render tests seed a catalogue but almost no activity, so the tables come up
 * empty — and an empty table never runs a single column callback, never touches a relation
 * and never orders by anything. Five separate 500s were sitting behind that: an ORDER BY on
 * a table that was never joined, three lazy-loading violations, and a cache payload that
 * could not be unserialised. None of them were reachable without rows.
 */
beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OrderSourceSeeder::class);
    $this->seed(DemoParitySeeder::class);
    $this->seed(DemoActivitySeeder::class);

    $admin = Admin::create([
        'name' => 'Populated Admin',
        'email' => 'populated@test.local',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $admin->assignRole('super_admin');
    $this->actingAs($admin, 'admin');
});

it('has rows to render', function (): void {
    // If this fails the rest of the file proves nothing.
    expect(Order::count())->toBeGreaterThan(0)
        ->and(OrderItem::count())->toBeGreaterThan(0)
        ->and(Ticket::count())->toBeGreaterThan(0);
});

it('renders every admin resource index with rows on it', function (): void {
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

it('renders every admin page with rows behind it', function (): void {
    $failures = [];

    foreach (Filament::getPanel('admin')->getPages() as $page) {
        $response = $this->get($page::getUrl());

        if ($response->getStatusCode() !== 200) {
            $failures[] = class_basename($page).' → '.$response->getStatusCode();
        }
    }

    expect($failures)->toBe([]);
});

it('opens a record in every admin resource that has one', function (): void {
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

it('renders the customer panel for a customer who has actually bought something', function (): void {
    $customer = Order::query()->whereNotNull('user_id')->first()?->user;

    expect($customer)->not->toBeNull();

    Filament::setCurrentPanel('customer');
    $this->actingAs($customer, 'web');

    $failures = [];

    foreach (Filament::getPanel('customer')->getResources() as $resource) {
        if (! $resource::hasPage('index')) {
            continue;
        }

        $response = $this->get($resource::getUrl('index'));

        if ($response->getStatusCode() !== 200) {
            $failures[] = class_basename($resource).' → '.$response->getStatusCode();
        }
    }

    foreach (Filament::getPanel('customer')->getPages() as $page) {
        $response = $this->get($page::getUrl());

        if ($response->getStatusCode() !== 200) {
            $failures[] = class_basename($page).' → '.$response->getStatusCode();
        }
    }

    expect($failures)->toBe([]);
});

/**
 * Dates read the same everywhere: "13 Aug, 2026 01:22 pm".
 *
 * Twelve-hour, day first. Four different formats had crept in between the two panels and
 * the invoice; they are all decided in one place now, so this asserts the place still
 * decides them.
 */
it('renders dates in one twelve-hour, day-first format', function (): void {
    $order = Order::query()->whereNotNull('paid_at')->firstOrFail();
    $expected = $order->paid_at->format(Dates::DATETIME);

    // Sanity: the format really is twelve-hour with a lower-case meridiem.
    expect($expected)->toMatch('/^\d{2} [A-Z][a-z]{2}, \d{4} \d{2}:\d{2} (am|pm)$/');

    $this->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertOk()
        ->assertSee($expected);
});

it('prints the same date format on the invoice', function (): void {
    $order = Order::query()->whereNotNull('paid_at')->with(['items', 'paymentMethod', 'user'])->firstOrFail();

    $html = view('pdf.invoice', ['order' => $order, 'print' => true])->render();

    expect($html)->toContain($order->paid_at->format(Dates::DATETIME));
});

/**
 * Staff must be able to tell a gateway capture from a transfer somebody verified by hand.
 * The method's own name does not say which: "bKash" is both.
 */
it('shows which kind of payment an order used', function (): void {
    $order = Order::query()->whereNotNull('payment_method_id')->with('paymentMethod')->firstOrFail();

    $this->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertOk()
        ->assertSee($order->paymentMethod->type->label());
});
