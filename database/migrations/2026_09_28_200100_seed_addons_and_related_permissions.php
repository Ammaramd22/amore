<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $newPerms = [
            'addons.view', 'addons.create', 'addons.edit', 'addons.delete',
            'subcategories.view', 'subcategories.create', 'subcategories.edit', 'subcategories.delete',
            'orders.refund', 'orders.comp',
            'pos.comp', 'pos.refund',
        ];

        foreach ($newPerms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $assign = [
            'software_owner' => $newPerms,
            'admin' => $newPerms,
            'manager' => [
                'addons.view', 'addons.create', 'addons.edit',
                'subcategories.view', 'subcategories.create', 'subcategories.edit',
                'orders.comp', 'pos.comp',
            ],
        ];

        foreach ($assign as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }
            $role->givePermissionTo($perms);
        }
    }

    public function down(): void
    {
        // Keep permissions on rollback — safer for live roles
    }
};
