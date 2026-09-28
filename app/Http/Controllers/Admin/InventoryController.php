<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingredient::latest();
        if ($request->filled('q')) {
            $query->where('name', 'like', "%{$request->q}%");
        }
        $ingredients = $query->paginate(20);
        return view('admin.inventory.index', compact('ingredients'));
    }

    public function movements(Request $request)
    {
        $query = StockMovement::with('ingredient', 'creator')->latest();
        if ($request->filled('ingredient')) {
            $query->where('ingredient_id', $request->ingredient);
        }
        $movements = $query->paginate(20);
        $ingredients = Ingredient::active()->get();
        return view('admin.inventory.movements', compact('movements', 'ingredients'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric',
            'reason' => 'required|string',
        ]);

        $ingredient = Ingredient::findOrFail($data['ingredient_id']);
        $before = $ingredient->stock_quantity;
        $ingredient->increment('stock_quantity', $data['quantity']);

        StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'adjustment',
            'quantity' => $data['quantity'],
            'stock_before' => $before,
            'stock_after' => $before + $data['quantity'],
            'unit' => $ingredient->unit,
            'reason' => $data['reason'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('inventory.index')->with('success', 'Stock adjusted.');
    }

    public function lowStock()
    {
        $ingredients = Ingredient::whereColumn('stock_quantity', '<=', 'reorder_level')->active()->get();
        return view('admin.inventory.low-stock', compact('ingredients'));
    }
}
