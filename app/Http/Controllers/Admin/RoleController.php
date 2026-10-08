<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /** Permission groups for the assign UI. */
    public const GROUPS = [
        'Dashboard' => ['dashboard'],
        'Users' => ['users'],
        'Roles' => ['roles'],
        'Branches' => ['branches'],
        'Categories' => ['categories'],
        'Add-ons' => ['addons'],
        'Subcategories' => ['subcategories'],
        'Products' => ['products'],
        'Ingredients' => ['ingredients'],
        'Recipes' => ['recipes'],
        'Suppliers' => ['suppliers'],
        'Purchases' => ['purchases'],
        'Customers' => ['customers'],
        'Billiards' => ['billiards'],
        'Floors' => ['floors'],
        'Tables' => ['tables'],
        'Orders' => ['orders'],
        'POS' => ['pos'],
        'Kitchen' => ['kitchen'],
        'Waiter' => ['waiter'],
        'Delivery' => ['delivery', 'delivery_partners'],
        'Displays' => ['displays'],
        'Marketing' => ['marketing', 'promos'],
        'Reports' => ['reports'],
        'Expenses' => ['expenses'],
        'Accounts' => ['accounts'],
        'Inventory' => ['inventory'],
        'Notifications' => ['notifications'],
        'Activity Logs' => ['activity_logs'],
        'Settings' => ['settings'],
        'Software Owner' => ['owner'],
    ];

    public function index()
    {
        abort_unless(auth()->user()?->can('roles.view') || auth()->user()?->can('users.view'), 403);

        $roles = Role::query()
            ->withCount('permissions', 'users')
            ->orderByRaw("FIELD(name, 'software_owner','admin','manager','cashier','waiter','kitchen','delivery')")
            ->orderBy('name')
            ->get()
            ->filter(function (Role $role) {
                if ($role->name === 'software_owner') {
                    return auth()->user()?->isSoftwareOwner();
                }

                return true;
            })
            ->values();

        return view('admin.roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        abort_unless(auth()->user()?->can('roles.edit') || auth()->user()?->can('roles.view'), 403);
        $this->guardRoleAccess($role);

        $grouped = $this->groupedPermissions();
        $assigned = $role->permissions->pluck('name')->all();
        $canEdit = auth()->user()?->can('roles.edit');

        return view('admin.roles.edit', compact('role', 'grouped', 'assigned', 'canEdit'));
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(auth()->user()?->can('roles.edit'), 403);
        $this->guardRoleAccess($role);

        $data = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $requested = $data['permissions'] ?? [];
        $allowed = $this->assignablePermissionNames();

        // Restaurant staff cannot grant owner-only permissions
        $requested = array_values(array_intersect($requested, $allowed));

        // software_owner role always keeps owner permissions if edited by owner
        if ($role->name === 'software_owner' && auth()->user()?->isSoftwareOwner()) {
            $ownerKeep = Permission::query()
                ->whereIn('name', ['owner.access', 'settings.system', 'settings.email', 'settings.pwa', 'settings.integrations'])
                ->pluck('name')
                ->all();
            $requested = array_values(array_unique(array_merge($requested, $ownerKeep)));
        }

        DB::transaction(function () use ($role, $requested) {
            $role->syncPermissions($requested);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('roles.edit', $role)
            ->with('success', 'Permissions updated for role “'.$role->name.'”.');
    }

    protected function guardRoleAccess(Role $role): void
    {
        if ($role->name === 'software_owner' && ! auth()->user()?->isSoftwareOwner()) {
            abort(403, 'Software owner role cannot be managed here.');
        }
    }

    /** @return list<string> */
    protected function assignablePermissionNames(): array
    {
        $query = Permission::query()->orderBy('name');

        if (! auth()->user()?->isSoftwareOwner()) {
            $query->whereNotIn('name', User::ownerOnlyPermissionNames());
        }

        return $query->pluck('name')->all();
    }

    /** @return array<string, \Illuminate\Support\Collection<int, Permission>> */
    protected function groupedPermissions(): array
    {
        $perms = Permission::query()
            ->orderBy('name')
            ->get()
            ->filter(function (Permission $p) {
                if (in_array($p->name, User::ownerOnlyPermissionNames(), true)) {
                    return auth()->user()?->isSoftwareOwner();
                }

                return true;
            });

        $grouped = [];
        foreach (self::GROUPS as $label => $prefixes) {
            $items = $perms->filter(function (Permission $p) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    if ($p->name === $prefix || str_starts_with($p->name, $prefix.'.')) {
                        return true;
                    }
                }

                return false;
            })->values();

            if ($items->isNotEmpty()) {
                $grouped[$label] = $items;
            }
        }

        $known = collect($grouped)->flatten()->pluck('name')->all();
        $extra = $perms->reject(fn (Permission $p) => in_array($p->name, $known, true))->values();
        if ($extra->isNotEmpty()) {
            $grouped['Other'] = $extra;
        }

        return $grouped;
    }
}
