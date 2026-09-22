<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.local',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $this->admin->assignRole('super_admin');

    $this->actingAs($this->admin, 'admin');
});

it('refuses the panel to an anonymous visitor', function (): void {
    auth('admin')->logout();

    $this->get('/admin')->assertRedirect();
});

it('refuses the panel to a suspended admin', function (): void {
    $this->admin->update(['status' => UserStatus::Suspended]);

    expect($this->admin->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
});

it('lists products', function (): void {
    $products = Product::factory()->count(3)->create();

    livewire(ListProducts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($products);
});

it('creates a category through the panel', function (): void {
    livewire(App\Filament\Admin\Resources\Categories\Pages\CreateCategory::class)
        ->fillForm([
            'name' => 'Streaming & OTT',
            'slug' => 'streaming-otx',
            'icon_key' => 'play',
            'gradient' => 'from-rose-500 to-red-700',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::where('slug', 'streaming-otx')->exists())->toBeTrue();
});

it('lists categories', function (): void {
    $categories = Category::factory()->count(2)->create();

    livewire(ListCategories::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($categories);
});

it('opens the orders queue', function (): void {
    livewire(ListOrders::class)->assertSuccessful();
});
