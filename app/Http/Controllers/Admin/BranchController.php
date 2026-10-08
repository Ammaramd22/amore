<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    protected function ensureEnabled(): void
    {
        abort_unless(BranchService::enabled(), 403, 'Multi-branch is disabled. Ask the software owner to enable it.');
    }

    public function index()
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('branches.view') || auth()->user()?->isSoftwareOwner(), 403);

        $branches = Branch::query()->ordered()->withCount(['assignedUsers', 'floors'])->get();

        return view('admin.branches.index', [
            'branches' => $branches,
            'maxBranches' => BranchService::maxBranches(),
            'canCreate' => BranchService::canCreateMore()
                && (auth()->user()?->can('branches.create') || auth()->user()?->isSoftwareOwner()),
            'remaining' => BranchService::remainingSlots(),
        ]);
    }

    public function create()
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('branches.create') || auth()->user()?->isSoftwareOwner(), 403);
        abort_unless(BranchService::canCreateMore(), 403, 'Branch limit reached. Ask the software owner to increase max branches.');

        return view('admin.branches.create', [
            'remaining' => BranchService::remainingSlots(),
            'maxBranches' => BranchService::maxBranches(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('branches.create') || auth()->user()?->isSoftwareOwner(), 403);
        abort_unless(BranchService::canCreateMore(), 422, 'Branch limit reached.');

        $data = $this->validated($request);
        unset($data['invoice_logo'], $data['remove_invoice_logo'], $data['is_main'], $data['is_active']);
        $data['sort_order'] = (int) Branch::query()->max('sort_order') + 1;
        $data['is_active'] = $request->boolean('is_active', true);
        $makeMain = $request->boolean('is_main') || Branch::query()->count() === 0;

        $branch = Branch::create($data);

        if ($request->hasFile('invoice_logo')) {
            $branch->update(['invoice_logo' => BranchService::storeLogo($branch, $request->file('invoice_logo'))]);
        }

        if ($makeMain) {
            $branch->markAsMain();
        }

        return redirect()->route('branches.index')->with('success', 'Branch created.');
    }

    public function edit(Branch $branch)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('branches.edit') || auth()->user()?->isSoftwareOwner(), 403);

        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('branches.edit') || auth()->user()?->isSoftwareOwner(), 403);

        $data = $this->validated($request, $branch);
        unset($data['invoice_logo'], $data['remove_invoice_logo'], $data['is_main'], $data['is_active']);
        $data['is_active'] = $request->boolean('is_active');
        $branch->update($data);

        if ($request->boolean('remove_invoice_logo')) {
            BranchService::deleteLogoFile($branch->invoice_logo);
            $branch->update(['invoice_logo' => null]);
        } elseif ($request->hasFile('invoice_logo')) {
            BranchService::deleteLogoFile($branch->invoice_logo);
            $branch->update(['invoice_logo' => BranchService::storeLogo($branch, $request->file('invoice_logo'))]);
        }

        if ($request->boolean('is_main')) {
            $branch->markAsMain();
        }

        return redirect()->route('branches.index')->with('success', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('branches.delete') || auth()->user()?->isSoftwareOwner(), 403);

        if ($branch->is_main) {
            return back()->with('error', 'Cannot delete the main branch. Set another branch as main first.');
        }

        if (Branch::query()->count() <= 1) {
            return back()->with('error', 'At least one branch is required.');
        }

        BranchService::deleteLogoFile($branch->invoice_logo);
        $branch->delete();

        if ((int) session(BranchService::SESSION_KEY) === (int) $branch->id) {
            session()->forget(BranchService::SESSION_KEY);
            BranchService::resolveAfterLogin(auth()->user());
        }

        return redirect()->route('branches.index')->with('success', 'Branch deleted.');
    }

    public function selectForm()
    {
        abort_unless(BranchService::enabled(), 403);
        $user = Auth::user();
        abort_unless($user, 403);

        $branches = BranchService::accessibleBranches($user);
        if ($branches->isEmpty()) {
            BranchService::resolveAfterLogin($user);

            return app(\App\Http\Controllers\Auth\LoginController::class)->afterBranchReady($user);
        }
        if ($branches->count() === 1) {
            BranchService::setCurrent($branches->first()->id, $user);

            return app(\App\Http\Controllers\Auth\LoginController::class)->afterBranchReady($user);
        }

        return view('auth.branch-select', [
            'branches' => $branches,
            'currentId' => BranchService::currentId(),
        ]);
    }

    public function selectStore(Request $request)
    {
        abort_unless(BranchService::enabled(), 403);
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        if (! BranchService::setCurrent((int) $data['branch_id'], $user)) {
            return back()->withErrors(['branch_id' => 'You do not have access to that branch.']);
        }

        return app(\App\Http\Controllers\Auth\LoginController::class)->afterBranchReady($user);
    }

    public function switch(Request $request)
    {
        abort_unless(BranchService::enabled(), 403);
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        if (! BranchService::setCurrent((int) $data['branch_id'], $user)) {
            return back()->with('error', 'You do not have access to that branch.');
        }

        $name = Branch::find($data['branch_id'])?->name ?? 'branch';

        return back()->with('success', 'Switched to '.$name);
    }

    protected function validated(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('branches', 'code')->ignore($branch?->id)->whereNull('deleted_at'),
            ],
            'company_name' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'receipt_footer' => ['nullable', 'string', 'max:200'],
            'invoice_logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'is_main' => ['nullable', 'boolean'],
            'remove_invoice_logo' => ['nullable', 'boolean'],
        ]);
    }
}
