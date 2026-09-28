<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Kitchen;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IceCreamDemoService
{
    /**
     * Ensure ice cream demo categories/products + images exist.
     * Safe to call repeatedly (upsert by slug/code).
     */
    public static function ensureSeeded(): array
    {
        $copied = self::ensureImagesOnDisk();
        $kitchenId = Kitchen::query()->value('id');

        $catalog = [
            'scoops' => [
                'name' => 'Scoops',
                'color' => '#f472b6',
                'products' => [
                    ['Vanilla Scoop', 180],
                    ['Chocolate Scoop', 180],
                    ['Strawberry Scoop', 190],
                    ['Mango Scoop', 200],
                    ['Cookies & Cream', 220],
                    ['Pistachio Scoop', 250],
                    ['Butterscotch', 220],
                    ['Mint Chocolate', 230],
                ],
            ],
            'soft-serve' => [
                'name' => 'Soft Serve',
                'color' => '#67e8f9',
                'products' => [
                    ['Vanilla Soft Serve', 150],
                    ['Chocolate Soft Serve', 150],
                    ['Twist Soft Serve', 170],
                    ['Strawberry Soft Serve', 170],
                ],
            ],
            'sundaes' => [
                'name' => 'Sundaes',
                'color' => '#fb7185',
                'products' => [
                    ['Hot Fudge Sundae', 350],
                    ['Banana Split', 420],
                    ['Brownie Sundae', 390],
                    ['Caramel Sundae', 360],
                ],
            ],
            'milkshakes' => [
                'name' => 'Milkshakes',
                'color' => '#c084fc',
                'products' => [
                    ['Vanilla Shake', 280],
                    ['Chocolate Shake', 280],
                    ['Strawberry Shake', 290],
                    ['Oreo Shake', 320],
                    ['Mango Shake', 300],
                ],
            ],
            'cones' => [
                'name' => 'Cones',
                'color' => '#fcd34d',
                'products' => [
                    ['Plain Cone', 40],
                    ['Waffle Cone', 80],
                    ['Chocolate Dip Cone', 100],
                    ['Double Cone', 60],
                ],
            ],
            'cups' => [
                'name' => 'Cups',
                'color' => '#86efac',
                'products' => [
                    ['Kids Cup', 120],
                    ['Regular Cup', 160],
                    ['Large Cup', 220],
                    ['Family Tub', 650],
                ],
            ],
            'toppings' => [
                'name' => 'Toppings',
                'color' => '#fda4af',
                'products' => [
                    ['Chocolate Sauce', 50],
                    ['Caramel Sauce', 50],
                    ['Sprinkles', 40],
                    ['Crushed Nuts', 60],
                    ['Marshmallows', 50],
                    ['Cherry Topping', 40],
                ],
            ],
            'specials' => [
                'name' => 'Specials',
                'color' => '#38bdf8',
                'products' => [
                    ['Ice Cream Sandwich', 280],
                    ['Affogato', 320],
                    ['Ice Cream Cake Slice', 450],
                    ['Seasonal Special', 380],
                ],
            ],
        ];

        $categoryIds = [];
        $productCount = 0;
        $order = 1;

        foreach ($catalog as $slug => $group) {
            $fullSlug = 'ice-'.$slug;
            $category = Category::withTrashed()->firstOrNew(['slug' => $fullSlug]);
            if ($category->trashed()) {
                $category->restore();
            }

            $catImage = 'categories/ice-'.$slug.'.png';
            if (! is_file(storage_path('app/public/'.$catImage))) {
                // soft-serve slug uses hyphen in folder name already
                $catImage = null;
            }

            $category->fill([
                'name' => $group['name'],
                'color' => $group['color'],
                'type' => 'direct',
                'kitchen_id' => $kitchenId,
                'display_order' => $order++,
                'is_active' => true,
                'show_in_pos' => true,
                'show_in_qr' => true,
                'description' => 'Ice cream — '.$group['name'],
                'image' => $catImage ?: $category->image,
            ]);
            $category->save();
            $categoryIds[] = (int) $category->id;

            $pOrder = 1;
            $codePrefix = 'IC-'.strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]+/', '', $slug), 0, 3));

            foreach ($group['products'] as [$name, $price]) {
                $code = $codePrefix.'-'.str_pad((string) $pOrder, 2, '0', STR_PAD_LEFT);
                $product = Product::withTrashed()->firstOrNew(['code' => $code]);
                if ($product->trashed()) {
                    $product->restore();
                }

                $imgRel = 'products/ice-v2-'.strtolower($code).'.jpg';
                $imgAbs = storage_path('app/public/'.$imgRel);
                $image = is_file($imgAbs) ? $imgRel : ($product->image ?: null);

                $product->fill([
                    'category_id' => $category->id,
                    'name' => $name,
                    'barcode' => $code,
                    'selling_price' => $price,
                    'cost_price' => round($price * 0.45, 2),
                    'routed_to' => 'kitchen',
                    'is_available' => true,
                    'show_in_pos' => true,
                    'show_in_qr' => true,
                    'track_stock' => true,
                    'stock_quantity' => max(100, (int) ($product->stock_quantity ?? 0)),
                    'display_order' => $pOrder++,
                    'has_variants' => false,
                    'has_addons' => false,
                    'image' => $image,
                ]);
                $product->save();
                $productCount++;
            }
        }

        Setting::set('bakery_category_ids', json_encode(array_values($categoryIds)), 'bakery', 'Categories shown in Bakery / Ice Cream POS UI', 'string');
        Setting::set('pos_ui_mode', 'ice_cream', 'pos', 'POS front UI mode', 'string');

        $summary = [
            'categories' => count($categoryIds),
            'products' => $productCount,
            'images_copied' => $copied,
        ];

        Log::info('Ice cream demo seeded', $summary);

        return $summary;
    }

    /**
     * Copy bundled demo images into storage/app/public if missing.
     */
    public static function ensureImagesOnDisk(): int
    {
        $copied = 0;
        $pairs = [
            [public_path('demo/ice-cream/products'), storage_path('app/public/products')],
            [public_path('demo/ice-cream/categories'), storage_path('app/public/categories')],
        ];

        foreach ($pairs as [$from, $to]) {
            if (! is_dir($from)) {
                continue;
            }
            File::ensureDirectoryExists($to);
            foreach (File::files($from) as $file) {
                $dest = $to.DIRECTORY_SEPARATOR.$file->getFilename();
                if (! is_file($dest)) {
                    File::copy($file->getPathname(), $dest);
                    $copied++;
                }
            }
        }

        return $copied;
    }
}
