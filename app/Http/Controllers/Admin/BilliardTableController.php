<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BilliardTable;
use App\Services\BilliardsService;
use Illuminate\Http\Request;

class BilliardTableController extends Controller
{
    protected function ensureEnabled(): void
    {
        abort_unless(BilliardsService::enabled(), 403, 'Billiards module is disabled. Ask the software owner to enable it.');
    }

    public function index()
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.tables') || auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        return view('admin.billiards.tables.index', [
            'tables' => BilliardTable::query()->ordered()->get(),
            'currency' => \App\Models\Setting::get('currency_symbol', 'LKR'),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.tables') || auth()->user()?->isSoftwareOwner(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:pool,snooker'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BilliardTable::create([
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Table added.');
    }

    public function update(Request $request, BilliardTable $billiardTable)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.tables') || auth()->user()?->isSoftwareOwner(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:pool,snooker'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $billiardTable->update([
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Table updated.');
    }

    public function destroy(BilliardTable $billiardTable)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.tables') || auth()->user()?->isSoftwareOwner(), 403);

        if ($billiardTable->bookings()->whereIn('status', ['booked', 'active'])->exists()) {
            return back()->withErrors(['table' => 'Cannot delete a table with active bookings.']);
        }

        $billiardTable->delete();

        return back()->with('success', 'Table deleted.');
    }
}
