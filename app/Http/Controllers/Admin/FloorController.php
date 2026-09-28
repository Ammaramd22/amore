<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index()
    {
        $query = Floor::withCount('tables')->latest();
        if (\App\Services\BranchService::enabled() && ($bid = \App\Services\BranchService::currentId())) {
            $query->where('branch_id', $bid);
        }
        $floors = $query->paginate(20);

        return view('admin.floors.index', compact('floors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'display_order' => 'nullable|integer']);
        $data['branch_id'] = \App\Services\BranchService::currentId()
            ?? \App\Models\Branch::query()->where('is_main', true)->value('id')
            ?? \App\Models\Branch::query()->value('id');
        abort_unless($data['branch_id'], 422, 'Create a branch first.');
        Floor::create($data);

        return redirect()->route('floors.index')->with('success', 'Floor created.');
    }

    public function update(Request $request, Floor $floor)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $floor->update($data);
        return redirect()->route('floors.index')->with('success', 'Floor updated.');
    }

    public function destroy(Floor $floor)
    {
        $floor->delete();
        return redirect()->route('floors.index')->with('success', 'Floor deleted.');
    }
}
