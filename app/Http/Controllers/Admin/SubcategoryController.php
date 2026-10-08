<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Subcategory::with('category')->withCount('products');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $subcategories = $query->orderBy('display_order')->orderBy('name')->paginate(20)->withQueryString();
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.subcategories.index', compact('subcategories', 'categories'));
    }

    public function create()
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.subcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subcategories,slug',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        if (Subcategory::withTrashed()->where('slug', $data['slug'])->exists()) {
            $data['slug'] = $data['slug'].'-'.Str::random(4);
        }
        $data['display_order'] = (int) ($data['display_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        Subcategory::create($data);

        return redirect()->route('subcategories.index')->with('success', 'Subcategory created.');
    }

    public function show(Subcategory $subcategory)
    {
        $subcategory->load('category')->loadCount('products');

        return view('admin.subcategories.show', compact('subcategory'));
    }

    public function edit(Subcategory $subcategory)
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.subcategories.edit', compact('subcategory', 'categories'));
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('subcategories', 'slug')->ignore($subcategory->id),
            ],
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['display_order'] = (int) ($data['display_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $subcategory->update($data);

        return redirect()->route('subcategories.index')->with('success', 'Subcategory updated.');
    }

    public function destroy(Subcategory $subcategory)
    {
        $subcategory->delete();

        return redirect()->route('subcategories.index')->with('success', 'Subcategory deleted.');
    }
}
