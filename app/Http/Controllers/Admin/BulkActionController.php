<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryPartner;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\RestaurantTable;
use App\Models\Floor;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

class BulkActionController extends Controller
{
    protected array $resources = [
        'products' => Product::class,
        'ingredients' => Ingredient::class,
        'categories' => Category::class,
        'customers' => Customer::class,
        'suppliers' => Supplier::class,
        'purchases' => Purchase::class,
        'floors' => Floor::class,
        'tables' => RestaurantTable::class,
        'orders' => Order::class,
        'expenses' => Expense::class,
        'users' => User::class,
        'delivery-partners' => DeliveryPartner::class,
        'kitchens' => Kitchen::class,
        'recipes' => Recipe::class,
    ];

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'resource' => 'required|string',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $key = $validated['resource'];
        if (!isset($this->resources[$key])) {
            return response()->json(['message' => 'Unknown resource.'], 422);
        }

        $modelClass = $this->resources[$key];
        $ids = array_values(array_unique($validated['ids']));

        if ($key === 'users') {
            $ids = array_values(array_filter($ids, fn ($id) => (int) $id !== (int) auth()->id()));
            if (!$ids) {
                return response()->json(['message' => 'You cannot delete your own account.'], 422);
            }
        }

        $query = $modelClass::query()->whereIn('id', $ids);
        $deleted = 0;

        foreach ($query->get() as $row) {
            try {
                $row->delete();
                $deleted++;
            } catch (\Throwable $e) {
                // skip protected rows
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => "Deleted {$deleted} item(s).",
                'deleted' => $deleted,
            ]);
        }

        return back()->with('success', "Deleted {$deleted} item(s).");
    }
}
