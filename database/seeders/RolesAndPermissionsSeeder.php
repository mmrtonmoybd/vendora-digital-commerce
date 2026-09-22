<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Staff roles.
 *
 * Shield generates the per-resource permissions; this seeder creates the roles and the few
 * business permissions that are not tied to a resource — the ones that gate money movement
 * and access fulfilment rather than a CRUD screen.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    /** Permissions that describe what someone may do, not which table they may see. */
    private const BUSINESS_PERMISSIONS = [
        'fulfill_orders',
        'change_delivery_email',
        'replace_access',
        'view_access_reference',
        'approve_payments_manually',
        'refund_orders',
        'adjust_wallet',
        'impersonate_customers',
        'view_reports',
        'manage_settings',
        'run_backups',
    ];

    private const ROLES = [
        'super_admin' => 'Full access, including staff accounts, roles, settings and backups.',
        'manager' => 'Everything except staff accounts, roles, settings and backups.',
        'order_manager' => 'Orders, fulfilment, deposits, invoices and read-only customers.',
        'support' => 'Tickets, messages and read-only orders and customers.',
        'content_editor' => 'CMS, blog, menus, sliders, FAQs, team and SEO.',
        'accountant' => 'Reports, the wallet ledger, deposits and withdrawals.',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::BUSINESS_PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        foreach (array_keys(self::ROLES) as $name) {
            Role::findOrCreate($name, 'admin');
        }

        // Super admin is granted by a Gate::before in the Shield configuration, so it needs
        // no explicit permissions; the others get exactly what their job requires.
        Role::findByName('manager', 'admin')->givePermissionTo([
            'fulfill_orders', 'change_delivery_email', 'replace_access', 'view_access_reference',
            'approve_payments_manually', 'refund_orders', 'adjust_wallet', 'view_reports',
        ]);

        Role::findByName('order_manager', 'admin')->givePermissionTo([
            'fulfill_orders', 'change_delivery_email', 'replace_access', 'view_access_reference',
            'approve_payments_manually',
        ]);

        Role::findByName('support', 'admin')->givePermissionTo([
            'fulfill_orders', 'change_delivery_email',
        ]);

        Role::findByName('accountant', 'admin')->givePermissionTo([
            'view_reports', 'refund_orders',
        ]);

        $this->grantResourcePermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Hand each role the Shield-generated resource permissions its job needs.
     *
     * Shield writes one permission per action per resource ("Update:Product"). Matching
     * them by resource name keeps this seeder correct after a new resource is added and
     * shield:generate is re-run, without listing 90 strings by hand.
     */
    private function grantResourcePermissions(): void
    {
        $readOnly = ['ViewAny', 'View'];
        // Shield also generates Restore/ForceDelete for soft-deleting models; the roles
        // below get the actions their job actually needs.
        $full = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Restore', 'ForceDelete'];

        $matrix = [
            'manager' => [
                'Product' => $full, 'Category' => $full, 'Order' => $full,
                'User' => ['ViewAny', 'View', 'Update'],
            ],
            'order_manager' => [
                'Order' => ['ViewAny', 'View', 'Update'],
                'Product' => $readOnly,
                'User' => $readOnly,
            ],
            'support' => [
                'Order' => $readOnly,
                'User' => $readOnly,
                'Product' => $readOnly,
            ],
            'content_editor' => [
                'Product' => $full,
                'Category' => $full,
            ],
            'accountant' => [
                'Order' => $readOnly,
                'User' => $readOnly,
            ],
        ];

        foreach ($matrix as $roleName => $resources) {
            $role = Role::findByName($roleName, 'admin');

            $names = [];

            foreach ($resources as $resource => $actions) {
                foreach ($actions as $action) {
                    $names[] = "{$action}:{$resource}";
                }
            }

            // findOrCreate rather than filtering to what already exists: a fresh install
            // runs `migrate --seed` and must end up with a working permission set without
            // anyone remembering to run shield:generate first.
            foreach ($names as $name) {
                Permission::findOrCreate($name, 'admin');
            }

            $role->givePermissionTo($names);
        }
    }
}
