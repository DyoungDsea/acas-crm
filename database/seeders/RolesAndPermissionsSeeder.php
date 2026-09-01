<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Super admins never need a role or permission check — they bypass
// authorization entirely via the Gate::before hook in
// AppServiceProvider. These roles/permissions exist only to
// distinguish company_admin from staff within a single company.
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'contacts.view', 'contacts.manage',
            'deals.view', 'deals.manage',
            'orders.view', 'orders.manage',
            'payments.record',
            'receipts.void',
            'products.manage',
            'users.manage',
            'settings.manage',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Company admin: everything within their own company —
        // company_id scoping is handled by CompanyScope, not by
        // limiting which permissions this role has.
        Role::firstOrCreate(['name' => 'company_admin', 'guard_name' => 'web'])
            ->syncPermissions($permissions);

        // Staff: day-to-day operational work, no user management and
        // no company settings changes. Adjust per how each of the 7
        // companies actually wants staff scoped — this is a
        // reasonable default, not a fixed rule.
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web'])
            ->syncPermissions([
                'contacts.view', 'contacts.manage',
                'deals.view', 'deals.manage',
                'orders.view', 'orders.manage',
                'payments.record',
            ]);
    }
}
