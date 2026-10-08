<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionSet;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptionSetController extends Controller
{
    public function index(Request $request)
    {
        $query = OptionSet::withCount(['options', 'products'])->ordered();

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->q.'%')
                    ->orWhere('display_name', 'like', '%'.$request->q.'%');
            });
        }
        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $sets = $query->paginate(20)->withQueryString();

        return view('admin.option-sets.index', compact('sets'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        $optionRows = [
            ['id' => null, 'name' => '', 'color' => '', 'is_active' => true],
        ];

        return view('admin.option-sets.create', compact('products', 'optionRows'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $set = DB::transaction(function () use ($request, $data) {
            $set = OptionSet::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'] ?: null,
                'type' => $data['type'] ?? 'text',
                'require_selection' => $request->boolean('require_selection', true),
                'is_active' => $request->boolean('is_active', true),
                'display_order' => (int) ($data['display_order'] ?? 0),
            ]);

            $this->syncOptions($set, $request->input('options', []));
            $this->syncProducts($set, $request->input('product_ids', []));

            return $set;
        });

        return redirect()->route('option-sets.edit', $set)->with('success', 'Option set created.');
    }

    public function show(OptionSet $option_set)
    {
        $option_set->load(['options', 'products']);

        return view('admin.option-sets.show', ['set' => $option_set]);
    }

    public function edit(OptionSet $option_set)
    {
        $option_set->load(['options', 'products']);
        $products = Product::orderBy('name')->get(['id', 'name']);
        $selectedProducts = $option_set->products->pluck('id')->all();
        $productUsage = $option_set->productUsageCount();
        $optionRows = $option_set->options->map(function ($opt) use ($option_set) {
            return [
                'id' => $opt->id,
                'name' => $opt->name,
                'color' => $opt->color,
                'is_active' => $opt->is_active,
                'variation_count' => $opt->itemVariationUsageCount(),
                'used_in_orders' => $opt->isUsedInOrders(),
            ];
        })->values()->all();

        if (! count($optionRows)) {
            $optionRows = [['id' => null, 'name' => '', 'color' => '', 'is_active' => true, 'variation_count' => 0, 'used_in_orders' => false]];
        }

        return view('admin.option-sets.edit', [
            'set' => $option_set,
            'products' => $products,
            'selectedProducts' => $selectedProducts,
            'optionRows' => $optionRows,
            'productUsage' => $productUsage,
        ]);
    }

    public function update(Request $request, OptionSet $option_set)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $option_set) {
            $option_set->update([
                'name' => $data['name'],
                'display_name' => $data['display_name'] ?: null,
                'type' => $data['type'] ?? 'text',
                'require_selection' => $request->boolean('require_selection', true),
                'is_active' => $request->boolean('is_active'),
                'display_order' => (int) ($data['display_order'] ?? 0),
            ]);

            $this->syncOptions($option_set, $request->input('options', []));
            $this->syncProducts($option_set, $request->input('product_ids', []));
        });

        return redirect()->route('option-sets.edit', $option_set)->with('success', 'Option set updated.');
    }

    public function destroy(OptionSet $option_set)
    {
        $inUse = Option::where('option_set_id', $option_set->id)
            ->whereHas('orderItemOptions')
            ->exists();

        if ($inUse) {
            return redirect()->route('option-sets.index')
                ->with('error', 'Cannot delete this option set — options are used on existing orders. Deactivate it instead.');
        }

        if ($option_set->options()->exists()) {
            return redirect()->route('option-sets.edit', $option_set)
                ->with('error', 'To delete an option set, first delete all options.');
        }

        $option_set->products()->detach();
        $option_set->delete();

        return redirect()->route('option-sets.index')->with('success', 'Option set deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'type' => 'required|in:text,text_color',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'require_selection' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|integer|exists:options,id',
            'options.*.name' => 'nullable|string|max:255',
            'options.*.color' => 'nullable|string|max:32',
            'options.*.is_active' => 'nullable',
            'options.*.remove' => 'nullable',
        ]);
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function syncOptions(OptionSet $set, array $rows): void
    {
        $keepIds = [];
        $order = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $optionId = ! empty($row['id']) ? (int) $row['id'] : null;
            $remove = filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($remove && $optionId) {
                $option = Option::where('option_set_id', $set->id)->whereKey($optionId)->first();
                if (! $option) {
                    continue;
                }
                if ($option->isUsedInOrders()) {
                    // Soft-retire: hide from POS, keep history
                    $option->update(['is_active' => false]);
                    $keepIds[] = $option->id;
                } else {
                    $option->delete();
                }
                continue;
            }

            if ($name === '') {
                continue;
            }

            $active = array_key_exists('is_active', $row)
                ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN)
                : true;
            $color = ($set->type === 'text_color') ? (trim((string) ($row['color'] ?? '')) ?: null) : null;

            if ($optionId) {
                $option = Option::where('option_set_id', $set->id)->whereKey($optionId)->first();
                if ($option) {
                    $option->update([
                        'name' => $name,
                        'color' => $color,
                        'display_order' => $order,
                        'is_active' => $active,
                    ]);
                    $keepIds[] = $option->id;
                    $order++;
                    continue;
                }
            }

            $created = Option::create([
                'option_set_id' => $set->id,
                'name' => $name,
                'color' => $color,
                'display_order' => $order,
                'is_active' => $active,
            ]);
            $keepIds[] = $created->id;
            $order++;
        }

        // Delete leftover unused options not submitted
        $set->options()
            ->whereNotIn('id', $keepIds)
            ->get()
            ->each(function (Option $option) {
                if ($option->isUsedInOrders()) {
                    $option->update(['is_active' => false]);
                } else {
                    $option->delete();
                }
            });
    }

    /** @param  array<int|string>  $productIds */
    private function syncProducts(OptionSet $set, array $productIds): void
    {
        $ids = collect($productIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $set->products()->sync($ids);
    }
}
