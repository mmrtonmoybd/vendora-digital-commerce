<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\Admin\Pages\Settings\GeneralSettingsPage;
use App\Filament\Admin\Pages\Settings\ShurjopaySettingsPage;
use App\Models\Admin;
use App\Settings\GeneralSettings;
use App\Settings\ShurjopaySettings;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = Admin::create([
        'name' => 'Owner', 'email' => 'owner@test.local',
        'password' => 'password', 'status' => UserStatus::Active,
    ]);
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin, 'admin');
});

it('saves a settings change and serves it immediately', function (): void {
    livewire(GeneralSettingsPage::class)
        ->fillForm(['site_name' => 'Renamed Shop', 'tagline' => 'A new tagline'])
        ->call('save')
        ->assertHasNoFormErrors();

    // Resolved fresh, proving the cache was cleared rather than still serving the old name.
    expect(app(GeneralSettings::class)->site_name)->toBe('Renamed Shop');
});

it('keeps settings away from staff without the permission', function (): void {
    $editor = Admin::create([
        'name' => 'Editor', 'email' => 'editor@test.local',
        'password' => 'password', 'status' => UserStatus::Active,
    ]);
    $editor->assignRole('content_editor');

    expect(GeneralSettingsPage::canAccess())->toBeTrue();

    $this->actingAs($editor, 'admin');

    expect(GeneralSettingsPage::canAccess())->toBeFalse();

    $this->get(GeneralSettingsPage::getUrl())->assertForbidden();
});

it('stores the gateway password encrypted', function (): void {
    $settings = app(ShurjopaySettings::class);
    $settings->password = 'super-secret-value';
    $settings->save();

    $stored = DB::table('settings')
        ->where('group', 'shurjopay')
        ->where('name', 'password')
        ->value('payload');

    // The raw row must not contain the secret in readable form.
    expect($stored)->not->toContain('super-secret-value')
        ->and(app(ShurjopaySettings::class)->password)->toBe('super-secret-value');
});

it('shows the callback urls staff must register with the gateway', function (): void {
    $urls = app(App\Services\ShurjopayService::class)->callbackUrls();

    expect($urls)->toHaveKeys(['return_url', 'cancel_url', 'ipn_url'])
        ->and($urls['ipn_url'])->toContain('/payments/shurjopay/ipn');
});

it('renders every settings page', function (): void {
    $failures = [];

    foreach (Filament\Facades\Filament::getPanel('admin')->getPages() as $page) {
        if (! str_contains($page, 'Pages\\Settings\\')) {
            continue;
        }

        $status = $this->get($page::getUrl())->getStatusCode();

        if ($status !== 200) {
            $failures[] = class_basename($page).' → '.$status;
        }
    }

    expect($failures)->toBe([]);
});
