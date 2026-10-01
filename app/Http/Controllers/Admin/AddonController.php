<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Product;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function index(Request $request)
    {
        $query = Addon::withCount('products')->ordered();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $addons = $query->paginate(20)->withQueryString();

        return view('admin.addons.index', compact('addons'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'has_addons']);

        return view('admin.addons.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['display_order'] = (int) ($data['display_order'] ?? 0);

        $addon = Addon::create($data);
        $productIds = collect($request->input('product_ids', []))->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $addon->products()->sync($productIds);
        $this->refreshProductAddonFlags($productIds);

        return redirect()->route('addons.index')->with('success', 'Modifier created.');
    }

    public function show(Addon $addon)
    {
        $addon->load(['products' => fn ($q) => $q->orderBy('name')]);

        return view('admin.addons.show', compact('addon'));
    }

    public function edit(Addon $addon)
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'has_addons']);
        $selected = $addon->products()->pluck('products.id')->all();

        return view('admin.addons.edit', compact('addon', 'products', 'selected'));
    }

    public function update(Request $request, Addon $addon)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['display_order'] = (int) ($data['display_order'] ?? 0);

        $previousIds = $addon->products()->pluck('products.id')->all();
        $addon->update($data);

        $productIds = collect($request->input('product_ids', []))->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $addon->products()->sync($productIds);
        $this->refreshProductAddonFlags(array_unique(array_merge($previousIds, $productIds)));

        return redirect()->route('addons.index')->with('success', 'Modifier updated.');
    }

    public function destroy(Addon $addon)
    {
        $productIds = $addon->products()->pluck('products.id')->all();
        $addon->products()->detach();
        $addon->delete();
        $this->refreshProductAddonFlags($productIds);

        return redirect()->route('addons.index')->with('success', 'Modifier deleted.');
    }

    /** @param  array<int>  $productIds */
    private function refreshProductAddonFlags(array $productIds): void
    {
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if (! $product) {
                continue;
            }
            $product->refreshHasAddonsFlag();
        }
    }
}
