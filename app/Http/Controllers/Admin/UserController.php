<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $query = User::with(['branch', 'branches', 'roles'])->latest();
        if (! auth()->user()?->isSoftwareOwner()) {
            $query->where(function ($q) {
                $q->where('user_type', '!=', 'software_owner')
                    ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'software_owner'));
            });
        }
        $users = $query->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::active()->ordered()->get();
        $roles = Role::whereIn('name', User::assignableRoleNames())->orderBy('name')->get();
        $multiBranch = \App\Services\BranchService::enabled();

        return view('admin.users.create', compact('branches', 'roles', 'multiBranch'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'pin' => ['nullable', 'regex:/^\d{4}$/', 'confirmed'],
            'user_type' => 'required|in:admin,manager,cashier,waiter,kitchen,delivery,staff,billiards',
            'username' => 'nullable|string|max:60|unique:users,username',
            'login_code' => ['nullable', 'regex:/^\d{4}$/', 'unique:users,login_code'],
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'branch_access' => 'nullable|in:all,single,multi',
            'employee_code' => 'nullable|string|max:50|unique:users',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'in:'.implode(',', User::assignableRoleNames()),
        ]);

        $data['password'] = Hash::make($data['password']);
        if (empty($data['pin'])) {
            unset($data['pin']);
        }
        if (empty($data['username'])) {
            $data['username'] = null;
        }
        if (empty($data['login_code'])) {
            $data['login_code'] = null;
        }
        unset($data['pin_confirmation'], $data['branch_ids'], $data['branch_access']);

        $user = User::create($data);
        if (! empty($request->input('roles'))) {
            $user->syncRoles($request->input('roles'));
        }
        $this->syncUserBranches($user, $request);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        if ($user->isSoftwareOwner() && ! auth()->user()?->isSoftwareOwner()) {
            abort(403, 'Software owner account cannot be edited here.');
        }

        $branches = Branch::active()->ordered()->get();
        $roles = Role::whereIn('name', User::assignableRoleNames())->orderBy('name')->get();
        $multiBranch = \App\Services\BranchService::enabled();
        $user->load('branches');

        return view('admin.users.edit', compact('user', 'branches', 'roles', 'multiBranch'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->isSoftwareOwner() && ! auth()->user()?->isSoftwareOwner()) {
            abort(403, 'Software owner account cannot be edited here.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'pin' => ['nullable', 'regex:/^\d{4}$/', 'confirmed'],
            'clear_pin' => 'nullable|boolean',
            'user_type' => 'required|in:admin,manager,cashier,waiter,kitchen,delivery,staff,billiards',
            'username' => ['nullable', 'string', 'max:60', Rule::unique('users', 'username')->ignore($user->id)],
            'login_code' => ['nullable', 'regex:/^\d{4}$/', Rule::unique('users', 'login_code')->ignore($user->id)],
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'branch_access' => 'nullable|in:all,single,multi',
            'employee_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'employee_code')->ignore($user->id),
            ],
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'in:'.implode(',', User::assignableRoleNames()),
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->boolean('clear_pin')) {
            $data['pin'] = null;
        } elseif (empty($data['pin'])) {
            unset($data['pin']);
        }

        if (array_key_exists('username', $data) && $data['username'] === '') {
            $data['username'] = null;
        }
        if (array_key_exists('login_code', $data) && $data['login_code'] === '') {
            $data['login_code'] = null;
        }

        unset($data['pin_confirmation'], $data['clear_pin'], $data['branch_ids'], $data['branch_access']);

        $user->update($data);
        if (isset($data['roles']) || $request->has('roles')) {
            $roles = $request->input('roles', []);
            if ($user->isSoftwareOwner() && auth()->user()?->isSoftwareOwner()) {
                $roles = array_values(array_unique(array_merge($roles, ['software_owner'])));
            }
            $user->syncRoles($roles);
        }
        $this->syncUserBranches($user, $request);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    protected function syncUserBranches(User $user, Request $request): void
    {
        if (! \App\Services\BranchService::enabled()) {
            // Legacy single branch_id only
            if ($request->filled('branch_id')) {
                $user->syncBranchAccess([(int) $request->input('branch_id')], (int) $request->input('branch_id'));
            } else {
                $user->branches()->detach();
                $user->update(['branch_id' => null]);
            }

            return;
        }

        $mode = $request->input('branch_access', 'all');
        if ($mode === 'all') {
            $user->branches()->detach();
            $user->update(['branch_id' => null]);

            return;
        }

        $ids = collect($request->input('branch_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($mode === 'single') {
            $one = $request->filled('branch_id') ? [(int) $request->input('branch_id')] : array_slice($ids, 0, 1);
            $ids = $one;
        }

        if (empty($ids)) {
            $user->branches()->detach();
            $user->update(['branch_id' => null]);

            return;
        }

        $default = $request->filled('branch_id') && in_array((int) $request->input('branch_id'), $ids, true)
            ? (int) $request->input('branch_id')
            : $ids[0];

        $user->syncBranchAccess($ids, $default);
    }

    public function destroy(User $user)
    {
        if ($user->isSoftwareOwner()) {
            return back()->with('error', 'Software owner account cannot be deleted.');
        }
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
