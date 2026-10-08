<?php

use Illuminate\Database\Migrations\Migration;
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

        $perms = [
            'option-sets.view', 'option-sets.create', 'option-sets.edit', 'option-sets.delete',
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $assign = [
            'software_owner' => $perms,
            'admin' => $perms,
            'manager' => ['option-sets.view', 'option-sets.create', 'option-sets.edit'],
        ];

        foreach ($assign as $roleName => $rolePerms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($rolePerms);
            }
        }
    }

    public function down(): void
    {
        // Keep permissions on rollback
    }
};
