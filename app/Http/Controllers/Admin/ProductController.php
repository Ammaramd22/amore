<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DeliveryPartner;
use App\Models\DeliveryPartnerProductPrice;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Services\CsvImportService;
use App\Services\OrderNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public const CODE_PREFIX = 'P-';

    public function index(Request $request)
    {
        $query = Product::with('category', 'subcategory')->latest();
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('type')) {
            $query->whereHas('category', fn($q) => $q->where('type', $request->type));
        }
        if ($request->filled('status')) {
            $query->where('is_available', $request->status === 'active' ? 1 : 0);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('code', 'like', "%{$request->q}%");
            });
        }
        $products = $query->paginate(20)->withQueryString();
        $categories = Category::active()->orderBy('name')->get();
        $totalValue = $query->clone()->sum(DB::raw('selling_price * stock_quantity'));
        return view('admin.products.index', compact('products', 'categories', 'totalValue'));
    }

    public function create()
    {
        $categories = Category::active()->get();
        $deliveryPartners = DeliveryPartner::active()->orderBy('name')->get();
        $nextProductCode = OrderNumberService::peekNext(self::CODE_PREFIX, 'products', false, null, 'code');
        $recentProductCodes = Product::query()
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'name', 'code', 'created_at']);
        $multiBranch = \App\Services\BranchService::enabled();
        $branches = $multiBranch ? \App\Models\Branch::active()->ordered()->get() : collect();
        $defaultBranchIds = $branches->pluck('id')->all();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $catalogAddons = \App\Models\Addon::ordered()->get();

        return view('admin.products.create', compact(
            'categories',
            'deliveryPartners',
            'nextProductCode',
            'recentProductCodes',
            'branches',
            'multiBranch',
            'defaultBranchIds',
            'suppliers',
            'catalogAddons'
        ));
    }

    public function subcategoriesByCategory(Category $category)
    {
        return response()->json($category->subcategories()->active()->get(['id', 'name']));
    }

    public function nextCode()
    {
        return response()->json([
            'code' => OrderNumberService::peekNext(self::CODE_PREFIX, 'products', false, null, 'code'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:products',
            'barcode' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'tax_inclusive' => 'boolean',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'has_variants' => 'boolean',
            'has_addons' => 'boolean',
            'is_combo' => 'boolean',
            'is_available' => 'boolean',
            'show_in_pos' => 'boolean',
            'show_in_qr' => 'boolean',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
            'partner_prices' => 'nullable|array',
            'partner_prices.*' => 'nullable|numeric|min:0',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'integer|exists:suppliers,id',
        ]);

        if (array_key_exists('expiry_date', $data) && blank($data['expiry_date'])) {
            $data['expiry_date'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $category = Category::find($data['category_id']);
        $data['routed_to'] = match($category?->type) {
            'bot' => 'bar',
            'direct' => 'kitchen',
            default => 'kitchen',
        };

        if (empty($data['code'])) {
            $data['code'] = OrderNumberService::generate(self::CODE_PREFIX, 'products', false, null, 'code');
        } elseif (Product::where('code', $data['code'])->exists()) {
            $data['code'] = OrderNumberService::generate(self::CODE_PREFIX, 'products', false, null, 'code');
        }

        unset($data['partner_prices'], $data['branch_ids'], $data['supplier_ids']);
        $product = Product::create($data);
        $this->syncPartnerPrices($product, $request->input('partner_prices', []));
        $this->syncProductBranches($product, $request);
        $this->syncProductSuppliers($product, $request);

        $variantCount = 0;
        foreach ($request->input('variants', []) as $variant) {
            if (! empty($variant['name'])) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variant['name'],
                    'sku' => $variant['sku'] ?? null,
                    'price_adjustment' => $variant['price_adjustment'] ?? 0,
                    'is_active' => true,
                ]);
                $variantCount++;
            }
        }

        $addonCount = 0;
        $sharedIds = collect($request->input('shared_addon_ids', []))->filter()->map(fn ($id) => (int) $id)->unique()->values();
        foreach ($request->input('addons', []) as $addon) {
            if (! empty($addon['name'])) {
                $shared = \App\Models\Addon::firstOrCreate(
                    [
                        'name' => $addon['name'],
                        'price' => (float) ($addon['price'] ?? 0),
                    ],
                    [
                        'is_active' => true,
                        'display_order' => 0,
                    ]
                );
                $sharedIds->push($shared->id);
                // Keep legacy row for backward compatibility during transition
                ProductAddon::create([
                    'product_id' => $product->id,
                    'name' => $addon['name'],
                    'price' => $addon['price'] ?? 0,
                    'is_active' => true,
                ]);
                $addonCount++;
            }
        }
        $product->sharedAddons()->sync($sharedIds->unique()->values()->all());
        $addonCount = max($addonCount, $sharedIds->count());

        if ($variantCount || $addonCount) {
            $product->update([
                'has_variants' => $variantCount > 0,
                'has_addons' => $addonCount > 0,
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'product' => $product]);
        }
        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function show(Product $product)
    {
        $product->load('category', 'subcategory', 'variants', 'addons', 'recipe.items');
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('variants', 'addons', 'sharedAddons', 'partnerPrices', 'branches', 'suppliers');
        $categories = Category::active()->get();
        $subcategories = Subcategory::where('category_id', $product->category_id)->active()->get();
        $deliveryPartners = DeliveryPartner::active()->orderBy('name')->get();
        $multiBranch = \App\Services\BranchService::enabled();
        $branches = $multiBranch ? \App\Models\Branch::active()->ordered()->get() : collect();
        $defaultBranchIds = $product->branches->isNotEmpty()
            ? $product->branches->pluck('id')->all()
            : $branches->pluck('id')->all();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $defaultSupplierIds = $product->suppliers->pluck('id')->all();
        $catalogAddons = \App\Models\Addon::ordered()->get();
        $selectedSharedAddonIds = $product->sharedAddons->pluck('id')->all();

        return view('admin.products.edit', compact(
            'product',
            'categories',
            'subcategories',
            'deliveryPartners',
            'branches',
            'multiBranch',
            'defaultBranchIds',
            'suppliers',
            'defaultSupplierIds',
            'catalogAddons',
            'selectedSharedAddonIds'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:products,code,' . $product->id,
            'barcode' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'tax_inclusive' => 'boolean',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'has_variants' => 'boolean',
            'has_addons' => 'boolean',
            'is_combo' => 'boolean',
            'is_available' => 'boolean',
            'show_in_pos' => 'boolean',
            'show_in_qr' => 'boolean',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
            'partner_prices' => 'nullable|array',
            'partner_prices.*' => 'nullable|numeric|min:0',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'integer|exists:suppliers,id',
        ]);

        if (array_key_exists('expiry_date', $data) && blank($data['expiry_date'])) {
            $data['expiry_date'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $category = Category::find($data['category_id']);
        $data['routed_to'] = match($category?->type) {
            'bot' => 'bar',
            'direct' => 'kitchen',
            default => 'kitchen',
        };

        unset($data['partner_prices'], $data['branch_ids'], $data['supplier_ids']);
        $product->update($data);
        $this->syncPartnerPrices($product, $request->input('partner_prices', []));
        $this->syncProductBranches($product, $request);
        $this->syncProductSuppliers($product, $request);

        // Sync portions (variants)
        $variantRows = collect($request->input('variants', []))
            ->filter(fn ($v) => ! empty($v['name']))
            ->values();
        $keepVariantIds = [];
        foreach ($variantRows as $variant) {
            if (! empty($variant['id'])) {
                $existing = $product->variants()->where('id', $variant['id'])->first();
                if ($existing) {
                    $existing->update([
                        'name' => $variant['name'],
                        'price_adjustment' => $variant['price_adjustment'] ?? 0,
                        'is_active' => true,
                    ]);
                    $keepVariantIds[] = $existing->id;
                    continue;
                }
            }
            $created = ProductVariant::create([
                'product_id' => $product->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'] ?? null,
                'price_adjustment' => $variant['price_adjustment'] ?? 0,
                'is_active' => true,
            ]);
            $keepVariantIds[] = $created->id;
        }
        $product->variants()->whereNotIn('id', $keepVariantIds)->delete();

        // Sync add-ons (legacy rows + shared catalog assignment)
        $addonRows = collect($request->input('addons', []))
            ->filter(fn ($a) => ! empty($a['name']))
            ->values();
        $keepAddonIds = [];
        $sharedIds = collect($request->input('shared_addon_ids', []))->filter()->map(fn ($id) => (int) $id)->unique()->values();
        foreach ($addonRows as $addon) {
            if (! empty($addon['id'])) {
                $existing = $product->addons()->where('id', $addon['id'])->first();
                if ($existing) {
                    $existing->update([
                        'name' => $addon['name'],
                        'price' => $addon['price'] ?? 0,
                        'is_active' => true,
                    ]);
                    $keepAddonIds[] = $existing->id;
                    $shared = \App\Models\Addon::firstOrCreate(
                        ['name' => $addon['name'], 'price' => (float) ($addon['price'] ?? 0)],
                        ['is_active' => true, 'display_order' => 0]
                    );
                    $sharedIds->push($shared->id);
                    continue;
                }
            }
            $created = ProductAddon::create([
                'product_id' => $product->id,
                'name' => $addon['name'],
                'price' => $addon['price'] ?? 0,
                'is_active' => true,
            ]);
            $keepAddonIds[] = $created->id;
            $shared = \App\Models\Addon::firstOrCreate(
                ['name' => $addon['name'], 'price' => (float) ($addon['price'] ?? 0)],
                ['is_active' => true, 'display_order' => 0]
            );
            $sharedIds->push($shared->id);
        }
        $product->addons()->whereNotIn('id', $keepAddonIds)->delete();
        $product->sharedAddons()->sync($sharedIds->unique()->values()->all());

        $product->update([
            'has_variants' => $product->variants()->exists(),
            'has_addons' => $product->sharedAddons()->exists() || $product->addons()->exists(),
        ]);

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function importTemplate()
    {
        $headers = [
            'name', 'code', 'category_name', 'category_type', 'subcategory_name', 'barcode', 'description',
            'cost_price', 'selling_price', 'tax_rate', 'tax_inclusive',
            'track_stock', 'stock_quantity', 'is_available', 'show_in_pos', 'show_in_qr',
        ];
        $sample = [
            'Chicken Kottu', 'CHK-KOTTU', 'Kottu', 'kot', '', '', 'Classic chicken kottu',
            '450', '850', '0', '0', '0', '0', '1', '1', '1',
        ];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'products-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request, CsvImportService $csv)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'max:5120'],
        ], [
            'csv_file.required' => 'CSV import failed: Please choose a CSV file.',
            'csv_file.file' => 'CSV import failed: Invalid upload.',
            'csv_file.max' => 'CSV import failed: File must be 5MB or smaller.',
        ]);

        $upload = $request->file('csv_file');
        $ext = strtolower((string) $upload->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return redirect()->route('products.index')
                ->with('error', 'CSV import failed: Invalid CSV header/format. Upload a .csv file.');
        }

        try {
            [$headers, $rows] = $csv->parse($upload);
        } catch (\Throwable $e) {
            return redirect()->route('products.index')
                ->with('error', $e->getMessage() ?: 'CSV import failed: Invalid CSV header/format.');
        }

        $requiredHeaders = ['name', 'category_name', 'selling_price'];
        $missingHeaders = array_values(array_filter($requiredHeaders, fn ($h) => ! in_array($h, $headers, true)));
        if ($missingHeaders !== []) {
            return redirect()->route('products.index')
                ->with('error', 'CSV import failed: Invalid CSV header/format. Missing column(s): '.implode(', ', $missingHeaders).'.');
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $categoryCache = [];
        $subcategoryCache = [];

        foreach ($rows as $row) {
            $line = $row['_line'] ?? '?';
            $name = trim((string) ($row['name'] ?? ''));
            $sellingRaw = $row['selling_price'] ?? '';
            $categoryName = trim((string) ($row['category_name'] ?? ''));

            if ($name === '') {
                $errors[] = "CSV import failed on row {$line}: Product name is required.";
                continue;
            }
            if ($categoryName === '') {
                $errors[] = "CSV import failed on row {$line}: category_name is required.";
                continue;
            }
            if ($sellingRaw === '' || $sellingRaw === null) {
                $errors[] = "CSV import failed on row {$line}: selling_price is required.";
                continue;
            }
            if (! $csv->isNumericValue($sellingRaw)) {
                $errors[] = "CSV import failed on row {$line}: selling_price must be a valid number.";
                continue;
            }

            $costRaw = $row['cost_price'] ?? '';
            if ($costRaw !== '' && $costRaw !== null && ! $csv->isNumericValue($costRaw)) {
                $errors[] = "CSV import failed on row {$line}: cost_price must be a valid number.";
                continue;
            }

            $taxRaw = $row['tax_rate'] ?? '';
            if ($taxRaw !== '' && $taxRaw !== null && ! $csv->isNumericValue($taxRaw)) {
                $errors[] = "CSV import failed on row {$line}: tax_rate must be a valid number.";
                continue;
            }

            $stockRaw = $row['stock_quantity'] ?? '';
            if ($stockRaw !== '' && $stockRaw !== null && ! $csv->isNumericValue($stockRaw)) {
                $errors[] = "CSV import failed on row {$line}: stock_quantity must be a valid number.";
                continue;
            }

            foreach (['tax_inclusive', 'track_stock', 'is_available', 'show_in_pos', 'show_in_qr'] as $boolField) {
                if (array_key_exists($boolField, $row) && ! $csv->isBoolToken($row[$boolField])) {
                    $errors[] = "CSV import failed on row {$line}: {$boolField} must be 0 or 1.";
                    continue 2;
                }
            }

            $cacheKey = mb_strtolower($categoryName);
            if (! isset($categoryCache[$cacheKey])) {
                $category = Category::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$cacheKey])
                    ->first();
                if (! $category) {
                    $errors[] = "CSV import failed on row {$line}: Category '{$categoryName}' does not exist.";
                    continue;
                }
                $categoryCache[$cacheKey] = $category;
            }
            $category = $categoryCache[$cacheKey];

            $subcategoryId = null;
            $subName = trim((string) ($row['subcategory_name'] ?? ''));
            if ($subName !== '') {
                $subKey = $category->id.'|'.mb_strtolower($subName);
                if (! isset($subcategoryCache[$subKey])) {
                    $sub = Subcategory::query()
                        ->where('category_id', $category->id)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($subName)])
                        ->first();
                    if (! $sub) {
                        $errors[] = "CSV import failed on row {$line}: Subcategory '{$subName}' does not exist for category '{$category->name}'.";
                        continue;
                    }
                    $subcategoryCache[$subKey] = $sub;
                }
                $subcategoryId = $subcategoryCache[$subKey]->id;
            }

            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '') {
                $code = OrderNumberService::generate(self::CODE_PREFIX, 'products', false, null, 'code');
            }

            $barcode = trim((string) ($row['barcode'] ?? ''));
            $barcode = $barcode === '' ? null : $barcode;

            $existing = Product::where('code', $code)->first();

            if ($barcode !== null) {
                $barcodeQuery = Product::where('barcode', $barcode);
                if ($existing) {
                    $barcodeQuery->where('id', '!=', $existing->id);
                }
                if ($barcodeQuery->exists()) {
                    $errors[] = "CSV import failed on row {$line}: Barcode already exists.";
                    continue;
                }
            }

            $payload = [
                'category_id' => $category->id,
                'subcategory_id' => $subcategoryId,
                'name' => $name,
                'code' => $code,
                'barcode' => $barcode,
                'description' => (($row['description'] ?? '') === '') ? null : ($row['description'] ?? null),
                'cost_price' => $csv->float($row['cost_price'] ?? 0),
                'selling_price' => $csv->float($sellingRaw),
                'tax_rate' => $csv->float($row['tax_rate'] ?? 0),
                'tax_inclusive' => $csv->bool($row['tax_inclusive'] ?? '0', false),
                'track_stock' => $csv->bool($row['track_stock'] ?? '0', false),
                'stock_quantity' => $csv->float($row['stock_quantity'] ?? 0),
                'is_available' => $csv->bool($row['is_available'] ?? '1', true),
                'show_in_pos' => $csv->bool($row['show_in_pos'] ?? '1', true),
                'show_in_qr' => $csv->bool($row['show_in_qr'] ?? '1', true),
                'routed_to' => match ($category->type) {
                    'bot' => 'bar',
                    default => 'kitchen',
                },
            ];

            try {
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    Product::create($payload);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "CSV import failed on row {$line}: ".$e->getMessage();
            }
        }

        $imported = $created + $updated;
        $failed = count($errors);

        if ($imported === 0 && $failed > 0) {
            return redirect()->route('products.index')
                ->with('error', 'CSV import failed — 0 products imported, '.$failed.' row(s) failed.')
                ->with('import_errors', array_slice($errors, 0, 50));
        }

        if ($imported > 0 && $failed === 0) {
            $message = 'CSV import successful — '.$imported.' product'.($imported === 1 ? '' : 's').' imported.';
            if ($updated > 0 && $created > 0) {
                $message .= " ({$created} created, {$updated} updated).";
            } elseif ($updated > 0) {
                $message .= " ({$updated} updated).";
            }

            return redirect()->route('products.index')->with('success', $message);
        }

        $message = 'CSV import completed — '.$imported.' product'.($imported === 1 ? '' : 's').' imported, '.$failed.' row'.($failed === 1 ? '' : 's').' failed.';

        return redirect()->route('products.index')
            ->with('success', $message)
            ->with('import_errors', array_slice($errors, 0, 50));
    }

    protected function syncPartnerPrices(Product $product, array $prices): void
    {
        $keep = [];
        foreach ($prices as $partnerId => $price) {
            if ($price === null || $price === '') {
                continue;
            }
            $partnerId = (int) $partnerId;
            $row = DeliveryPartnerProductPrice::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'delivery_partner_id' => $partnerId,
                ],
                ['price' => round((float) $price, 2)]
            );
            $keep[] = $row->id;
        }

        DeliveryPartnerProductPrice::query()
            ->where('product_id', $product->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->delete();
    }

    protected function syncProductBranches(Product $product, Request $request): void
    {
        if (! \App\Services\BranchService::enabled()) {
            return;
        }

        $ids = $request->input('branch_ids', []);
        if (! is_array($ids) || $ids === []) {
            // Empty = all branches (no pivot rows)
            $product->branches()->detach();

            return;
        }

        $product->syncBranches($ids);
    }

    protected function syncProductSuppliers(Product $product, Request $request): void
    {
                $ids = $request->input('supplier_ids', []);
        if (! is_array($ids) || $ids === []) {
            $product->suppliers()->detach();

            return;
        }

        $product->syncSuppliers($ids);
    }
}
