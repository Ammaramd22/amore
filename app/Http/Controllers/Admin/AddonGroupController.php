<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\AddonGroup;
use App\Models\Branch;
use App\Models\Product;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddonGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = AddonGroup::withCount(['addons', 'products'])->ordered();

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->q.'%')
                    ->orWhere('display_name', 'like', '%'.$request->q.'%');
            });
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $groups = $query->paginate(20)->withQueryString();

        return view('admin.addon-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.addon-groups.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $group = DB::transaction(function () use ($request, $data) {
            $group = AddonGroup::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'] ?: null,
                'selection_type' => $data['selection_type'] ?? 'list',
                'require_selection' => $request->boolean('require_selection'),
                'allow_multiple' => $request->boolean('allow_multiple', true),
                'hide_on_receipt' => $request->boolean('hide_on_receipt'),
                'show_in_pos' => $request->boolean('show_in_pos', true),
                'display_order' => (int) ($data['display_order'] ?? 0),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncModifierRows($group, $request->input('modifiers', []));
            $productIds = $this->syncProducts($group, [], $request->input('product_ids', []));
            $this->syncBranchSelection($group, $request);
            $this->refreshProductFlags($productIds);

            return $group;
        });

        return redirect()->route('addon-groups.edit', $group)->with('success', 'Modifier created.');
    }

    public function show(AddonGroup $addon_group)
    {
        $addon_group->load([
            'addons' => fn ($q) => $q->ordered(),
            'products' => fn ($q) => $q->orderBy('name'),
            'branches',
        ]);

        return view('admin.addon-groups.show', ['group' => $addon_group]);
    }

    public function edit(AddonGroup $addon_group)
    {
        $addon_group->load(['addons', 'products', 'branches']);

        return view('admin.addon-groups.edit', array_merge($this->formData($addon_group), [
            'group' => $addon_group,
        ]));
    }

    public function update(Request $request, AddonGroup $addon_group)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $addon_group) {
            $previousProductIds = $addon_group->products()->pluck('products.id')->all();

            $addon_group->update([
                'name' => $data['name'],
                'display_name' => $data['display_name'] ?: null,
                'selection_type' => $data['selection_type'] ?? 'list',
                'require_selection' => $request->boolean('require_selection'),
                'allow_multiple' => $request->boolean('allow_multiple', true),
                'hide_on_receipt' => $request->boolean('hide_on_receipt'),
                'show_in_pos' => $request->boolean('show_in_pos', true),
                'display_order' => (int) ($data['display_order'] ?? 0),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncModifierRows($addon_group, $request->input('modifiers', []));
            $productIds = $this->syncProducts($addon_group, $previousProductIds, $request->input('product_ids', []));
            $this->syncBranchSelection($addon_group, $request);
            $this->refreshProductFlags($productIds);
        });

        return redirect()->route('addon-groups.edit', $addon_group)->with('success', 'Modifier updated.');
    }

    public function destroy(AddonGroup $addon_group)
    {
        $productIds = $addon_group->products()->pluck('products.id')->all();
        $addon_group->addons()->detach();
        $addon_group->products()->detach();
        $addon_group->branches()->detach();
        $addon_group->delete();
        $this->refreshProductFlags($productIds);

        return redirect()->route('addon-groups.index')->with('success', 'Modifier deleted.');
    }

    private function formData(?AddonGroup $group = null): array
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'has_addons']);
        $multiBranch = BranchService::enabled();
        $branches = $multiBranch ? Branch::active()->ordered()->get() : collect();
        $defaultBranchIds = $group
            ? ($group->branches->isNotEmpty() ? $group->branches->pluck('id')->all() : $branches->pluck('id')->all())
            : $branches->pluck('id')->all();
        $selectedProducts = $group
            ? $group->products->pluck('id')->all()
            : [];
        $modifierRows = $group
            ? $group->addons->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'price' => (float) $a->price,
                'is_preselected' => (bool) ($a->pivot->is_preselected ?? false),
                'is_available' => (bool) ($a->pivot->is_available ?? true),
            ])->values()->all()
            : [['id' => null, 'name' => '', 'price' => 0, 'is_preselected' => false, 'is_available' => true]];

        return compact('products', 'multiBranch', 'branches', 'defaultBranchIds', 'selectedProducts', 'modifierRows');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'selection_type' => 'nullable|in:list',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'require_selection' => 'boolean',
            'allow_multiple' => 'boolean',
            'hide_on_receipt' => 'boolean',
            'show_in_pos' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'modifiers' => 'nullable|array',
            'modifiers.*.id' => 'nullable|integer|exists:addons,id',
            'modifiers.*.name' => 'nullable|string|max:255',
            'modifiers.*.price' => 'nullable|numeric|min:0',
            'modifiers.*.is_preselected' => 'nullable|boolean',
            'modifiers.*.is_available' => 'nullable|boolean',
        ]);
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function syncModifierRows(AddonGroup $group, array $rows): void
    {
        $sync = [];
        $order = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $price = (float) ($row['price'] ?? 0);
            $addonId = ! empty($row['id']) ? (int) $row['id'] : null;
            $available = array_key_exists('is_available', $row)
                ? filter_var($row['is_available'], FILTER_VALIDATE_BOOLEAN)
                : true;
            $preselected = array_key_exists('is_preselected', $row)
                ? filter_var($row['is_preselected'], FILTER_VALIDATE_BOOLEAN)
                : false;

            if ($addonId) {
                $addon = Addon::find($addonId);
                if ($addon) {
                    $addon->update([
                        'name' => $name,
                        'price' => $price,
                        // Keep catalog item active; POS availability is pivot is_available only.
                        'is_active' => true,
                        'display_order' => $order,
                    ]);
                } else {
                    $addon = Addon::create([
                        'name' => $name,
                        'price' => $price,
                        'is_active' => true,
                        'display_order' => $order,
                    ]);
                    $addonId = $addon->id;
                }
            } else {
                $addon = Addon::create([
                    'name' => $name,
                    'price' => $price,
                    'is_active' => true,
                    'display_order' => $order,
                ]);
                $addonId = $addon->id;
            }

            $sync[$addonId] = ['display_order' => $order];
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('addon_group_addon', 'is_preselected')) {
                    $sync[$addonId]['is_preselected'] = $preselected;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('addon_group_addon', 'is_available')) {
                    $sync[$addonId]['is_available'] = $available;
                }
            } catch (\Throwable $e) {
                // ignore schema probe failures
            }
            $order++;
        }

        $group->addons()->sync($sync);
    }

    /**
     * @param  array<int>  $previousProductIds
     * @param  array<int|string>  $productIds
     * @return array<int>
     */
    private function syncProducts(AddonGroup $group, array $previousProductIds, array $productIds): array
    {
        $ids = collect($productIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $group->products()->sync($ids);

        return array_values(array_unique(array_merge($previousProductIds, $ids)));
    }

    private function syncBranchSelection(AddonGroup $group, Request $request): void
    {
        if (! BranchService::enabled()) {
            $group->branches()->detach();

            return;
        }

        $ids = collect($request->input('branch_ids', []))->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $group->syncBranches($ids);
    }

    /** @param  array<int>  $productIds */
    private function refreshProductFlags(array $productIds): void
    {
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $product->refreshHasAddonsFlag();
            }
        }
    }
}
