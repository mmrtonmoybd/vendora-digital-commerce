<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Admins\Pages\ListAdmins;
use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Models\Admin;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function adminWithRole(string $role): Admin
{
    $admin = Admin::create([
        'name' => ucfirst($role),
        'email' => $role.'@test.local',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $admin->assignRole($role);

    return $admin;
}

it('lets a super admin reach everything without listing permissions', function (): void {
    $this->actingAs(adminWithRole('super_admin'), 'admin');

    $this->get(ListProducts::getUrl())->assertSuccessful();
    $this->get(ListAdmins::getUrl())->assertSuccessful();
});

it('keeps support staff out of the catalogue editor', function (): void {
    $support = adminWithRole('support');

    // Support may read products to answer a question about an order …
    expect($support->can('ViewAny:Product'))->toBeTrue();
    // … but may not change the catalogue or the price of anything.
    expect($support->can('Update:Product'))->toBeFalse();
    expect($support->can('Create:Product'))->toBeFalse();
    expect($support->can('Delete:Product'))->toBeFalse();
});

it('keeps everyone but a super admin out of staff accounts', function (string $role): void {
    $admin = adminWithRole($role);

    expect($admin->can('ViewAny:Admin'))->toBeFalse();
    expect($admin->can('Create:Admin'))->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->get(ListAdmins::getUrl())
        ->assertForbidden();
})->with(['manager', 'order_manager', 'support', 'content_editor', 'accountant']);

it('denies the money permissions to roles that should not move money', function (): void {
    expect(adminWithRole('support')->can('adjust_wallet'))->toBeFalse();
    expect(adminWithRole('content_editor')->can('refund_orders'))->toBeFalse();

    // A manager runs the shop day to day and does need these.
    $manager = adminWithRole('manager');
    expect($manager->can('adjust_wallet'))->toBeTrue();
    expect($manager->can('approve_payments_manually'))->toBeTrue();
});

it('only lets staff with the permission see an internal access reference', function (): void {
    expect(adminWithRole('order_manager')->can('view_access_reference'))->toBeTrue();
    expect(adminWithRole('accountant')->can('view_access_reference'))->toBeFalse();
});
