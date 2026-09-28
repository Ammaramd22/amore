<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Floor;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\RestaurantTable;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'address' => '123 Galle Road, Colombo',
                'phone' => '0112345678',
                'email' => 'main@restaurant.lk',
                'is_active' => true,
                'is_main' => true,
            ]
        );

        $floor = Floor::firstOrCreate(
            ['name' => 'Ground Floor', 'branch_id' => $branch->id],
            ['display_order' => 1, 'is_active' => true]
        );

        $tables = [
            ['name' => 'Table 1', 'number' => 'T01', 'capacity' => 4],
            ['name' => 'Table 2', 'number' => 'T02', 'capacity' => 4],
            ['name' => 'Table 3', 'number' => 'T03', 'capacity' => 6],
            ['name' => 'Table 4', 'number' => 'T04', 'capacity' => 2],
            ['name' => 'Table 5', 'number' => 'T05', 'capacity' => 8],
            ['name' => 'Table 6', 'number' => 'T06', 'capacity' => 4],
        ];
        foreach ($tables as $t) {
            RestaurantTable::firstOrCreate(
                ['number' => $t['number'], 'floor_id' => $floor->id],
                array_merge($t, ['status' => 'available', 'shape' => 'square', 'is_active' => true])
            );
        }

        $categories = [
            ['name' => 'Rice & Curry', 'slug' => 'rice-curry', 'color' => '#e74c3c'],
            ['name' => 'Kottu', 'slug' => 'kottu', 'color' => '#f39c12'],
            ['name' => 'Biryani', 'slug' => 'biryani', 'color' => '#27ae60'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'color' => '#3498db'],
            ['name' => 'Desserts', 'slug' => 'desserts', 'color' => '#9b59b6'],
            ['name' => 'Short Eats', 'slug' => 'short-eats', 'color' => '#e67e22'],
        ];
        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['is_active' => true, 'show_in_pos' => true, 'show_in_qr' => true])
            );
        }

        $products = [
            ['name' => 'Chicken Rice & Curry', 'code' => 'CHRC001', 'selling_price' => 450, 'category_slug' => 'rice-curry', 'routed_to' => 'kitchen'],
            ['name' => 'Fish Rice & Curry', 'code' => 'FIRC001', 'selling_price' => 380, 'category_slug' => 'rice-curry', 'routed_to' => 'kitchen'],
            ['name' => 'Vegetable Rice & Curry', 'code' => 'VERC001', 'selling_price' => 300, 'category_slug' => 'rice-curry', 'routed_to' => 'kitchen'],
            ['name' => 'Chicken Kottu', 'code' => 'CHKT001', 'selling_price' => 550, 'category_slug' => 'kottu', 'routed_to' => 'kitchen'],
            ['name' => 'Cheese Kottu', 'code' => 'CHKT002', 'selling_price' => 600, 'category_slug' => 'kottu', 'routed_to' => 'kitchen'],
            ['name' => 'Seafood Kottu', 'code' => 'SFKT001', 'selling_price' => 650, 'category_slug' => 'kottu', 'routed_to' => 'kitchen'],
            ['name' => 'Chicken Biryani', 'code' => 'CHBR001', 'selling_price' => 580, 'category_slug' => 'biryani', 'routed_to' => 'kitchen'],
            ['name' => 'Mutton Biryani', 'code' => 'MTBR001', 'selling_price' => 750, 'category_slug' => 'biryani', 'routed_to' => 'kitchen'],
            ['name' => 'Lime Juice', 'code' => 'LMJV001', 'selling_price' => 120, 'category_slug' => 'beverages', 'routed_to' => 'bar'],
            ['name' => 'Milk Shake', 'code' => 'MLSH001', 'selling_price' => 280, 'category_slug' => 'beverages', 'routed_to' => 'bar'],
            ['name' => 'Watalappan', 'code' => 'WTLP001', 'selling_price' => 180, 'category_slug' => 'desserts', 'routed_to' => 'kitchen'],
            ['name' => 'Caramel Pudding', 'code' => 'CRPD001', 'selling_price' => 150, 'category_slug' => 'desserts', 'routed_to' => 'kitchen'],
            ['name' => 'Fish Roll', 'code' => 'FSRL001', 'selling_price' => 60, 'category_slug' => 'short-eats', 'routed_to' => 'kitchen'],
            ['name' => 'Vegetable Patty', 'code' => 'VEPT001', 'selling_price' => 50, 'category_slug' => 'short-eats', 'routed_to' => 'kitchen'],
        ];

        foreach ($products as $prod) {
            $category = Category::where('slug', $prod['category_slug'])->first();
            if ($category) {
                Product::firstOrCreate(
                    ['code' => $prod['code']],
                    [
                        'category_id' => $category->id,
                        'name' => $prod['name'],
                        'selling_price' => $prod['selling_price'],
                        'routed_to' => $prod['routed_to'],
                        'is_available' => true,
                        'show_in_pos' => true,
                        'show_in_qr' => true,
                        'track_stock' => false,
                    ]
                );
            }
        }

        $chickenKottu = Product::where('code', 'CHKT001')->first();
        if ($chickenKottu) {
            ProductVariant::firstOrCreate(
                ['product_id' => $chickenKottu->id, 'name' => 'Small'],
                ['price_adjustment' => -100, 'is_active' => true]
            );
            ProductVariant::firstOrCreate(
                ['product_id' => $chickenKottu->id, 'name' => 'Large'],
                ['price_adjustment' => 100, 'is_active' => true]
            );
        }

        $chickenBiryani = Product::where('code', 'CHBR001')->first();
        if ($chickenBiryani) {
            ProductAddon::firstOrCreate(
                ['product_id' => $chickenBiryani->id, 'name' => 'Extra Chicken'],
                ['price' => 150, 'is_active' => true]
            );
            ProductAddon::firstOrCreate(
                ['product_id' => $chickenBiryani->id, 'name' => 'Raita'],
                ['price' => 50, 'is_active' => true]
            );
        }

        $ingredients = [
            ['name' => 'Rice', 'code' => 'ING001', 'unit' => 'kg', 'stock_quantity' => 50, 'reorder_level' => 10, 'cost_per_unit' => 120],
            ['name' => 'Chicken', 'code' => 'ING002', 'unit' => 'kg', 'stock_quantity' => 20, 'reorder_level' => 5, 'cost_per_unit' => 800],
            ['name' => 'Fish', 'code' => 'ING003', 'unit' => 'kg', 'stock_quantity' => 15, 'reorder_level' => 3, 'cost_per_unit' => 600],
            ['name' => 'Vegetables Mix', 'code' => 'ING004', 'unit' => 'kg', 'stock_quantity' => 10, 'reorder_level' => 2, 'cost_per_unit' => 200],
            ['name' => 'Godamba Roti', 'code' => 'ING005', 'unit' => 'pcs', 'stock_quantity' => 100, 'reorder_level' => 20, 'cost_per_unit' => 25],
            ['name' => 'Cheese', 'code' => 'ING006', 'unit' => 'kg', 'stock_quantity' => 5, 'reorder_level' => 1, 'cost_per_unit' => 1500],
            ['name' => 'Mutton', 'code' => 'ING007', 'unit' => 'kg', 'stock_quantity' => 8, 'reorder_level' => 2, 'cost_per_unit' => 1800],
            ['name' => 'Lime', 'code' => 'ING008', 'unit' => 'pcs', 'stock_quantity' => 50, 'reorder_level' => 10, 'cost_per_unit' => 15],
            ['name' => 'Milk', 'code' => 'ING009', 'unit' => 'litre', 'stock_quantity' => 20, 'reorder_level' => 5, 'cost_per_unit' => 180],
            ['name' => 'Coconut Milk', 'code' => 'ING010', 'unit' => 'litre', 'stock_quantity' => 10, 'reorder_level' => 2, 'cost_per_unit' => 250],
            ['name' => 'Eggs', 'code' => 'ING011', 'unit' => 'pcs', 'stock_quantity' => 60, 'reorder_level' => 12, 'cost_per_unit' => 25],
            ['name' => 'Jaggery', 'code' => 'ING012', 'unit' => 'kg', 'stock_quantity' => 5, 'reorder_level' => 1, 'cost_per_unit' => 350],
            ['name' => 'Curry Leaves', 'code' => 'ING013', 'unit' => 'g', 'stock_quantity' => 500, 'reorder_level' => 100, 'cost_per_unit' => 2],
            ['name' => 'Spices Mix', 'code' => 'ING014', 'unit' => 'kg', 'stock_quantity' => 3, 'reorder_level' => 0.5, 'cost_per_unit' => 600],
        ];
        foreach ($ingredients as $ing) {
            Ingredient::firstOrCreate(
                ['code' => $ing['code']],
                array_merge($ing, ['is_active' => true])
            );
        }

        Supplier::firstOrCreate(
            ['name' => 'Ceylon Fresh Foods'],
            [
                'branch_id' => $branch->id,
                'contact_person' => 'Mr. Perera',
                'phone' => '0112345678',
                'email' => 'orders@ceylonfresh.lk',
                'address' => 'Pettah, Colombo',
                'city' => 'Colombo',
                'is_active' => true,
            ]
        );

        $rice = Product::where('code', 'CHRC001')->first();
        if ($rice) {
            $recipe = Recipe::firstOrCreate(
                ['product_id' => $rice->id],
                ['total_cost' => 0, 'wastage_percentage' => 5, 'yield_quantity' => 1]
            );
            $ingRice = Ingredient::where('code', 'ING001')->first();
            $ingChicken = Ingredient::where('code', 'ING002')->first();
            if ($ingRice) {
                RecipeItem::firstOrCreate(
                    ['recipe_id' => $recipe->id, 'ingredient_id' => $ingRice->id],
                    ['quantity' => 0.3, 'unit' => 'kg', 'cost' => 36]
                );
            }
            if ($ingChicken) {
                RecipeItem::firstOrCreate(
                    ['recipe_id' => $recipe->id, 'ingredient_id' => $ingChicken->id],
                    ['quantity' => 0.15, 'unit' => 'kg', 'cost' => 120]
                );
            }
        }

        $customers = [
            ['name' => 'Kamal Perera', 'phone' => '0771112223', 'address' => 'Colombo 05'],
            ['name' => 'Sunil Silva', 'phone' => '0772223334', 'address' => 'Nugegoda'],
            ['name' => 'Anjali Fernando', 'phone' => '0773334445', 'address' => 'Dehiwala'],
            ['name' => 'Ranjith Bandara', 'phone' => '0774445556', 'address' => 'Maharagama'],
        ];
        foreach ($customers as $cust) {
            Customer::firstOrCreate(
                ['phone' => $cust['phone']],
                array_merge($cust, ['is_active' => true, 'loyalty_points' => 0, 'outstanding_balance' => 0])
            );
        }
    }
}
