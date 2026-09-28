<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kitchen;
use App\Models\Category;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        $kitchens = Kitchen::withCount('categories')->orderBy('name')->paginate(20);
        return view('admin.kitchens.index', compact('kitchens'));
    }

    public function create()
    {
        return view('admin.kitchens.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:kitchens',
            'type' => 'required|in:kot,bot',
            'description' => 'nullable|string',
            'printer_name' => 'nullable|string|max:255',
            'printer_ip' => 'nullable|ip',
            'printer_port' => 'nullable|integer|min:1|max:65535',
            'print_mode' => 'nullable|in:direct,preview',
            'auto_print' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['auto_print'] = $request->boolean('auto_print', false);
        $validated['printer_port'] = (int) ($validated['printer_port'] ?? 9100) ?: 9100;
        $validated['print_mode'] = ($validated['print_mode'] ?? 'preview') === 'direct' ? 'direct' : 'preview';

        Kitchen::create($validated);

        return redirect()->route('kitchens.index')
            ->with('success', 'Kitchen created successfully');
    }

    public function edit(Kitchen $kitchen)
    {
        $categories = Category::with('kitchen:id,name')
            ->orderByRaw("FIELD(type, 'kot', 'bot', 'direct')")
            ->orderBy('name')
            ->get();

        return view('admin.kitchens.edit', compact('kitchen', 'categories'));
    }

    public function update(Request $request, Kitchen $kitchen)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:kitchens,code,'.$kitchen->id,
            'type' => 'required|in:kot,bot',
            'description' => 'nullable|string',
            'printer_name' => 'nullable|string|max:255',
            'printer_ip' => 'nullable|ip',
            'printer_port' => 'nullable|integer|min:1|max:65535',
            'print_mode' => 'nullable|in:direct,preview',
            'auto_print' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['auto_print'] = $request->boolean('auto_print', false);
        $validated['printer_port'] = (int) ($validated['printer_port'] ?? 9100) ?: 9100;
        $validated['print_mode'] = ($validated['print_mode'] ?? 'preview') === 'direct' ? 'direct' : 'preview';

        $kitchen->update($validated);

        return redirect()->route('kitchens.edit', $kitchen)
            ->with('success', 'Kitchen updated successfully');
    }

    public function assignCategories(Request $request, Kitchen $kitchen)
    {
        $validated = $request->validate([
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        $ids = collect($validated['categories'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Assign selected categories to this kitchen
        if ($ids !== []) {
            Category::whereIn('id', $ids)->update(['kitchen_id' => $kitchen->id]);
        }

        // Unassign any previously linked categories that were unchecked
        Category::where('kitchen_id', $kitchen->id)
            ->when($ids !== [], fn ($q) => $q->whereNotIn('id', $ids))
            ->when($ids === [], fn ($q) => $q)
            ->update(['kitchen_id' => null]);

        return redirect()->route('kitchens.edit', $kitchen)
            ->with('success', 'Categories assigned to '.$kitchen->name.'.');
    }

    public function destroy(Kitchen $kitchen)
    {
        // Reset kitchen_id for related categories
        Category::where('kitchen_id', $kitchen->id)->update(['kitchen_id' => null]);
        $kitchen->delete();

        return redirect()->route('kitchens.index')
            ->with('success', 'Kitchen deleted successfully');
    }
}
