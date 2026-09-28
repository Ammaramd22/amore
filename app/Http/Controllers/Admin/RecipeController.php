<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with('product')->latest()->paginate(20);
        return view('admin.recipes.index', compact('recipes'));
    }

    public function create()
    {
        $products = Product::doesntHave('recipe')->available()->get();
        $ingredients = Ingredient::active()->get();
        return view('admin.recipes.create', compact('products', 'ingredients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id|unique:recipes',
            'wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'instructions' => 'nullable|string',
            'yield_quantity' => 'nullable|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string|max:20',
        ]);

        $recipe = Recipe::create([
            'product_id' => $data['product_id'],
            'wastage_percentage' => $data['wastage_percentage'] ?? 0,
            'instructions' => $data['instructions'] ?? null,
            'yield_quantity' => $data['yield_quantity'] ?? 1,
        ]);

        $totalCost = 0;
        foreach ($data['items'] as $item) {
            $ingredient = Ingredient::find($item['ingredient_id']);
            $cost = $ingredient->cost_per_unit * $item['quantity'];
            $totalCost += $cost;
            RecipeItem::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $item['ingredient_id'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'cost' => $cost,
            ]);
        }
        $recipe->update(['total_cost' => $totalCost]);

        return redirect()->route('recipes.index')->with('success', 'Recipe created.');
    }

    public function show(Recipe $recipe)
    {
        $recipe->load('product', 'items.ingredient');
        return view('admin.recipes.show', compact('recipe'));
    }

    public function edit(Recipe $recipe)
    {
        $recipe->load('items.ingredient');
        $ingredients = Ingredient::active()->get();
        return view('admin.recipes.edit', compact('recipe', 'ingredients'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $data = $request->validate([
            'wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'instructions' => 'nullable|string',
            'yield_quantity' => 'nullable|integer|min:1',
        ]);
        $recipe->update($data);
        return redirect()->route('recipes.index')->with('success', 'Recipe updated.');
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->items()->delete();
        $recipe->delete();
        return redirect()->route('recipes.index')->with('success', 'Recipe deleted.');
    }
}
