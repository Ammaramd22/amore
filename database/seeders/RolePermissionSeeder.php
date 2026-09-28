<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Users & roles
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',

            // Branches
            'branches.view', 'branches.create', 'branches.edit', 'branches.delete',

            // Menu
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'ingredients.view', 'ingredients.create', 'ingredients.edit', 'ingredients.delete',
            'recipes.view', 'recipes.create', 'recipes.edit', 'recipes.delete',

            // Purchasing
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete',

            // CRM
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',

            // Billiards
            'billiards.access', 'billiards.tables', 'billiards.bookings', 'billiards.pay', 'billiards.reports', 'billiards.display',

            // Floor
            'floors.view', 'floors.create', 'floors.edit', 'floors.delete',
            'tables.view', 'tables.create', 'tables.edit', 'tables.delete',

            // Orders / POS
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete', 'orders.void',
            'pos.access', 'pos.hold', 'pos.discount', 'pos.void',

            // Kitchen / waiter / delivery
            'kitchen.view', 'kitchen.prepare', 'kitchen.ready', 'kitchen.serve', 'kitchen.manage',
            'waiter.panel', 'waiter.orders', 'waiter.tables',
            'delivery.view', 'delivery.manage',
            'delivery_partners.view', 'delivery_partners.manage',

            // Displays & marketing
            'displays.view', 'displays.manage',
            'marketing.view', 'marketing.manage',
            'promos.view', 'promos.manage',

            // Finance
            'reports.view', 'reports.export',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',

            // Inventory
            'inventory.view', 'inventory.adjust', 'inventory.transfer',

            // Notifications / logs
            'notifications.view', 'notifications.send',
            'activity_logs.view',

            // Restaurant settings (business, tax, POS, waiter, kitchen, prefixes, QR)
            'settings.view', 'settings.edit',

            // Software-owner only (never granted to restaurant roles)
            'settings.system',
            'settings.email',
            'settings.pwa',
            'settings.integrations',
            'owner.access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $ownerOnly = [
            'settings.system',
            'settings.email',
            'settings.pwa',
            'settings.integrations',
            'owner.access',
        ];

        $restaurantAll = array_values(array_diff($permissions, $ownerOnly));

        $roles = [
            'software_owner' => $permissions,

            'admin' => $restaurantAll,

            'manager' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.edit',
                'categories.view', 'categories.create', 'categories.edit',
                'products.view', 'products.create', 'products.edit',
                'ingredients.view', 'ingredients.create', 'ingredients.edit',
                'recipes.view', 'recipes.create', 'recipes.edit',
                'suppliers.view', 'suppliers.create', 'suppliers.edit',
                'purchases.view', 'purchases.create', 'purchases.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'billiards.access', 'billiards.tables', 'billiards.bookings', 'billiards.pay', 'billiards.reports', 'billiards.display',
                'floors.view', 'floors.create', 'floors.edit',
                'tables.view', 'tables.create', 'tables.edit',
                'orders.view', 'orders.create', 'orders.edit',
                'pos.access', 'pos.hold', 'pos.discount',
                'kitchen.view', 'kitchen.prepare', 'kitchen.ready', 'kitchen.serve', 'kitchen.manage',
                'waiter.panel', 'waiter.orders', 'waiter.tables',
                'delivery.view', 'delivery.manage',
                'delivery_partners.view', 'delivery_partners.manage',
                'displays.view', 'displays.manage',
                'marketing.view', 'marketing.manage',
                'promos.view', 'promos.manage',
                'reports.view', 'reports.export',
                'settings.view', 'settings.edit',
                'expenses.view', 'expenses.create', 'expenses.edit',
                'accounts.view', 'accounts.create', 'accounts.edit',
                'inventory.view', 'inventory.adjust',
                'notifications.view', 'notifications.send',
                'activity_logs.view',
            ],

            'cashier' => [
                'dashboard.view',
                'pos.access', 'pos.hold', 'pos.discount',
                'orders.view', 'orders.create', 'orders.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'tables.view',
                'products.view',
                'reports.view',
                'displays.view',
            ],

            'waiter' => [
                'waiter.panel', 'waiter.orders', 'waiter.tables',
                'orders.view', 'orders.create',
                'customers.view', 'customers.create',
                'products.view',
            ],

            'kitchen' => [
                'kitchen.view', 'kitchen.prepare', 'kitchen.ready', 'kitchen.serve',
                'orders.view',
            ],

            'delivery' => [
                'delivery.view', 'delivery.manage',
                'orders.view',
            ],

            'billiards' => [
                'billiards.access', 'billiards.tables', 'billiards.bookings', 'billiards.pay', 'billiards.reports', 'billiards.display',
                'customers.view', 'customers.create',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        // Ensure restaurant admin never keeps owner-only perms if re-seeded after old grants
        $adminRole = Role::findByName('admin', 'web');
        $adminRole->revokePermissionTo($ownerOnly);
    }
}
