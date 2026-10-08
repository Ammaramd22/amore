<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Kitchen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::withCount('products')->with('kitchen');
        if ($request->filled('q')) {
            $query->where('name', 'like', "%{$request->q}%");
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('kitchen')) {
            $query->where('kitchen_id', $request->kitchen);
        }
        $categories = $query->latest()->paginate(20)->withQueryString();
        $kitchens = Kitchen::active()->orderBy('name')->get();
        return view('admin.categories.index', compact('categories', 'kitchens'));
    }

    public function create()
    {
        $kitchens = Kitchen::active()->get();
        return view('admin.categories.create', compact('kitchens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'display_order' => 'nullable|integer',
            'show_in_pos' => 'boolean',
            'show_in_qr' => 'boolean',
            'type' => 'required|in:kot,direct,bot',
            'kitchen_id' => 'nullable|exists:kitchens,id',
            'image' => 'nullable|image|max:2048',
        ]);
        $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        $data['show_in_pos'] = $request->boolean('show_in_pos');
        $data['show_in_qr'] = $request->boolean('show_in_qr');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($data);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'category' => $category]);
        }
        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $kitchens = Kitchen::active()->get();
        return view('admin.categories.edit', compact('category', 'kitchens'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'display_order' => 'nullable|integer',
            'show_in_pos' => 'boolean',
            'show_in_qr' => 'boolean',
            'is_active' => 'boolean',
            'type' => 'required|in:kot,direct,bot',
            'kitchen_id' => 'nullable|exists:kitchens,id',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);
        $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        $data['show_in_pos'] = $request->boolean('show_in_pos');
        $data['show_in_qr'] = $request->boolean('show_in_qr');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image') && $category->image) {
            Storage::disk('public')->delete($category->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        unset($data['remove_image']);
        $category->update($data);
        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
