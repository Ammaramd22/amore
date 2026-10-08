<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LabelLayout;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BarcodeLabelController extends Controller
{
    public const EXPORT_RELATIVE = 'exports/barcode_labels.csv';

    public const COLUMNS = [
        'sku' => 'SKU / Code',
        'barcode' => 'Barcode',
        'name' => 'Product name',
        'category' => 'Category',
        'selling_price' => 'Selling price',
        'cost_price' => 'Cost price',
        'qty' => 'Label copies (qty)',
    ];

    public function index()
    {
        $this->ensureSettings();
        LabelLayout::ensureDefault();

        $categories = Category::active()
            ->where('type', 'direct')
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = $this->directProductsQuery()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'barcode', 'selling_price', 'cost_price', 'category_id'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'text' => $p->name.($p->code ? ' ('.$p->code.')' : ''),
                'name' => $p->name,
                'code' => $p->code,
                'barcode' => $p->barcode ?: $p->code,
                'selling_price' => (float) $p->selling_price,
                'cost_price' => (float) $p->cost_price,
                'category_id' => (int) $p->category_id,
                'category' => $p->category?->name,
            ])
            ->values();

        $saveServer = (bool) Setting::get('bartender_save_server', true);
        $downloadBrowser = (bool) Setting::get('bartender_download_browser', true);
        $labelPath = (string) Setting::get('bartender_label_path', 'C:\\Labels\\BarcodePrint.btw');
        $showOpenBtn = (bool) Setting::get('bartender_show_open_btn', false);
        $fixedUrl = url(self::EXPORT_RELATIVE);
        $columns = self::COLUMNS;
        $directCount = $products->count();
        $layouts = LabelLayout::query()->orderByDesc('is_default')->orderBy('name')->get();
        $defaultLayout = LabelLayout::ensureDefault();
        $currency = Setting::get('currency_symbol', 'LKR');

        return view('admin.barcode-labels.index', compact(
            'categories',
            'products',
            'saveServer',
            'downloadBrowser',
            'labelPath',
            'showOpenBtn',
            'fixedUrl',
            'columns',
            'directCount',
            'layouts',
            'defaultLayout',
            'currency'
        ));
    }

    public function designer(?LabelLayout $layout = null)
    {
        $this->ensureSettings();
        LabelLayout::ensureDefault();

        $layouts = LabelLayout::query()->orderByDesc('is_default')->orderBy('name')->get();
        $layout = $layout && $layout->exists
            ? $layout
            : ($layouts->firstWhere('is_default') ?: $layouts->first());

        $config = $layout->normalizedConfig();
        if ($config['business_name'] === '') {
            $config['business_name'] = (string) Setting::get('company_name', '');
        }

        $googleFonts = LabelLayout::googleFonts();
        $currency = Setting::get('currency_symbol', 'LKR');
        if (trim((string) ($config['currency_prefix'] ?? '')) === '') {
            $config['currency_prefix'] = $currency.' ';
        }

        $labels = [[
            'name' => 'Sample Product',
            'code' => 'SKU-1001',
            'barcode' => '1044',
            'selling_price' => 650.00,
            'cost_price' => 320.00,
            'cost_code' => LabelLayout::costCodeFromAmount(320),
            'category' => 'General',
        ]];

        return view('admin.barcode-labels.browser', [
            'layouts' => $layouts,
            'layout' => $layout,
            'config' => $config,
            'googleFonts' => $googleFonts,
            'currency' => $currency,
            'labels' => $labels,
            'labelCount' => 1,
            'mode' => 'designer',
            'fontUrl' => $layout->fontCssUrl($config),
            'saveUrl' => route('barcode-labels.layouts.update', $layout),
            'saveAsUrl' => route('barcode-labels.layouts.store'),
            'closeUrl' => route('barcode-labels.index'),
        ]);
    }

    public function storeLayout(Request $request)
    {
        $data = $this->validateLayout($request);

        $layout = DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                LabelLayout::query()->update(['is_default' => false]);
            }

            return LabelLayout::create([
                'name' => $data['name'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'config' => $data['config'],
            ]);
        });

        if (! LabelLayout::query()->where('is_default', true)->exists()) {
            $layout->update(['is_default' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Layout saved',
            'layout' => $layout->fresh(),
            'redirect' => route('barcode-labels.designer', $layout),
        ]);
    }

    public function updateLayout(Request $request, LabelLayout $layout)
    {
        $data = $this->validateLayout($request);

        DB::transaction(function () use ($layout, $data) {
            if (! empty($data['is_default'])) {
                LabelLayout::query()->where('id', '!=', $layout->id)->update(['is_default' => false]);
            }

            $layout->update([
                'name' => $data['name'],
                'is_default' => (bool) ($data['is_default'] ?? true),
                'config' => $data['config'],
            ]);
        });

        if (! LabelLayout::query()->where('is_default', true)->exists()) {
            $layout->update(['is_default' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Layout saved',
            'layout' => $layout->fresh(),
        ]);
    }

    public function destroyLayout(LabelLayout $layout)
    {
        if (LabelLayout::query()->count() <= 1) {
            return response()->json(['success' => false, 'message' => 'Keep at least one layout.'], 422);
        }

        $wasDefault = $layout->is_default;
        $layout->delete();

        if ($wasDefault) {
            LabelLayout::ensureDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Layout deleted',
            'redirect' => route('barcode-labels.designer'),
        ]);
    }

    public function browserPrint(Request $request)
    {
        $validated = $request->validate([
            'layout_id' => 'nullable|integer|exists:label_layouts,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'nullable|integer|min:1|max:999',
        ]);

        LabelLayout::ensureDefault();
        $layout = ! empty($validated['layout_id'])
            ? LabelLayout::findOrFail($validated['layout_id'])
            : LabelLayout::query()->where('is_default', true)->firstOrFail();

        $ids = collect($validated['items'])->pluck('id')->unique()->values();
        $products = $this->directProductsQuery()
            ->with('category:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $labels = [];
        foreach ($validated['items'] as $item) {
            $product = $products->get((int) $item['id']);
            if (! $product) {
                continue;
            }
            $copies = max(1, (int) ($item['qty'] ?? 1));
            $payload = [
                'name' => $product->name,
                'code' => $product->code ?? '',
                'barcode' => $product->barcode ?: ($product->code ?? ''),
                'selling_price' => (float) $product->selling_price,
                'cost_price' => (float) $product->cost_price,
                'cost_code' => LabelLayout::costCodeFromAmount((float) $product->cost_price),
                'category' => $product->category?->name ?? '',
            ];
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = $payload;
            }
        }

        if ($labels === []) {
            return back()->with('error', 'Only Direct (stock) products can be printed.');
        }

        $config = $layout->normalizedConfig();
        if ($config['business_name'] === '') {
            $config['business_name'] = (string) Setting::get('company_name', '');
        }
        $currency = Setting::get('currency_symbol', 'LKR');
        if (trim((string) ($config['currency_prefix'] ?? '')) === '') {
            $config['currency_prefix'] = $currency.' ';
        }

        $taxRate = (float) Setting::get('tax_rate', 0);
        $taxEnabled = (bool) Setting::get('tax_enabled', false);

        return view('admin.barcode-labels.browser', [
            'layouts' => LabelLayout::query()->orderByDesc('is_default')->orderBy('name')->get(),
            'layout' => $layout,
            'config' => $config,
            'googleFonts' => LabelLayout::googleFonts(),
            'currency' => $currency,
            'labels' => $labels,
            'labelCount' => count($labels),
            'mode' => 'print',
            'fontUrl' => $layout->fontCssUrl($config),
            'taxRate' => $taxRate,
            'taxEnabled' => $taxEnabled,
            'saveUrl' => route('barcode-labels.layouts.update', $layout),
            'saveAsUrl' => route('barcode-labels.layouts.store'),
            'closeUrl' => route('barcode-labels.index'),
        ]);
    }

    protected function validateLayout(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'is_default' => 'nullable|boolean',
            'config' => 'required|array',
            'config.width_mm' => 'required|numeric|min:10|max:200',
            'config.height_mm' => 'required|numeric|min:10|max:200',
            'config.business_name' => 'nullable|string|max:120',
            'config.font_family' => 'required|string|max:80',
            'config.price_type' => 'nullable|in:inc_tax,ex_tax',
            'config.currency_prefix' => 'nullable|string|max:20',
            'config.barcode' => 'required|array',
            'config.barcode.position' => 'required|in:top,middle,bottom,left,right',
            'config.barcode.width' => 'required|numeric|min:20|max:120',
            'config.barcode.height' => 'required|numeric|min:8|max:80',
            'config.barcode.thickness' => 'required|numeric|min:1|max:5',
            'config.fonts' => 'required|array',
            'config.show' => 'required|array',
        ]);

        $defaults = LabelLayout::defaultConfig();
        $fonts = array_keys(LabelLayout::googleFonts());
        if (! in_array($data['config']['font_family'], $fonts, true)) {
            $data['config']['font_family'] = 'Roboto';
        }

        $cfg = LabelLayout::normalizeConfig($data['config']);
        $cfg['width_mm'] = round((float) $data['config']['width_mm'], 2);
        $cfg['height_mm'] = round((float) $data['config']['height_mm'], 2);
        $cfg['business_name'] = (string) ($data['config']['business_name'] ?? '');
        $cfg['font_family'] = $data['config']['font_family'];
        $cfg['price_type'] = $data['config']['price_type'] ?? 'inc_tax';
        $cfg['currency_prefix'] = (string) ($data['config']['currency_prefix'] ?? $defaults['currency_prefix']);

        foreach (['business_name', 'item_name', 'sku', 'price', 'cost_code'] as $key) {
            $cfg['fonts'][$key] = max(4, min(48, (float) ($data['config']['fonts'][$key] ?? $defaults['fonts'][$key])));
            $cfg['show'][$key] = filter_var($data['config']['show'][$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        $cfg['show']['barcode'] = filter_var($data['config']['show']['barcode'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $pos = $data['config']['barcode']['position'];
        if (in_array($pos, ['top', 'left'], true)) {
            $pos = 'left';
        } else {
            $pos = 'right';
        }
        $cfg['barcode'] = [
            'position' => $pos,
            'width' => max(20, min(120, (float) $data['config']['barcode']['width'])),
            'height' => max(8, min(80, (float) $data['config']['barcode']['height'])),
            'thickness' => max(1, min(5, (float) $data['config']['barcode']['thickness'])),
        ];

        // Horizontal / landscape: width is the longer edge
        if ($cfg['height_mm'] > $cfg['width_mm']) {
            $swap = $cfg['width_mm'];
            $cfg['width_mm'] = $cfg['height_mm'];
            $cfg['height_mm'] = $swap;
        }

        $data['config'] = $cfg;
        $data['is_default'] = filter_var($data['is_default'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return $data;
    }

    /** Select2 AJAX — direct / stock products only (no KOT / BOT menu) */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $categoryId = $request->get('category_id');

        $products = $this->directProductsQuery()
            ->with('category:id,name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('barcode', 'like', "%{$q}%");
                });
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'code', 'barcode', 'selling_price', 'cost_price', 'category_id']);

        return response()->json([
            'results' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'text' => $p->name.($p->code ? ' ('.$p->code.')' : '').($p->barcode ? ' · '.$p->barcode : ''),
                'name' => $p->name,
                'code' => $p->code,
                'barcode' => $p->barcode ?: $p->code,
                'selling_price' => (float) $p->selling_price,
                'cost_price' => (float) $p->cost_price,
                'category' => $p->category?->name,
            ]),
        ]);
    }

    public function byCategory(Category $category)
    {
        if (($category->type ?? '') !== 'direct') {
            return response()->json(['products' => [], 'message' => 'Only Direct categories can print labels'], 422);
        }

        $products = $this->directProductsQuery()
            ->where('category_id', $category->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'barcode', 'selling_price', 'cost_price', 'category_id']);

        return response()->json([
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'barcode' => $p->barcode ?: $p->code,
                'selling_price' => (float) $p->selling_price,
                'cost_price' => (float) $p->cost_price,
                'category' => $category->name,
            ]),
        ]);
    }

    public function export(Request $request)
    {
        $this->ensureSettings();

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'nullable|integer|min:1|max:999',
            'columns' => 'required|array|min:1',
            'columns.*' => 'string|in:'.implode(',', array_keys(self::COLUMNS)),
        ]);

        $columns = array_values(array_intersect(array_keys(self::COLUMNS), $validated['columns']));
        if ($columns === []) {
            return back()->with('error', 'Select at least one CSV column.');
        }

        $ids = collect($validated['items'])->pluck('id')->unique()->values();
        $qtyMap = collect($validated['items'])->mapWithKeys(fn ($row) => [
            (int) $row['id'] => max(1, (int) ($row['qty'] ?? 1)),
        ]);

        $products = $this->directProductsQuery()
            ->with('category:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($validated['items'] as $item) {
            $product = $products->get((int) $item['id']);
            if (! $product) {
                continue;
            }
            $copies = max(1, (int) ($item['qty'] ?? $qtyMap[$product->id] ?? 1));
            $payload = [
                'sku' => $product->code ?? '',
                'barcode' => $product->barcode ?: ($product->code ?? ''),
                'name' => $product->name,
                'category' => $product->category?->name ?? '',
                'selling_price' => number_format((float) $product->selling_price, 2, '.', ''),
                'cost_price' => number_format((float) $product->cost_price, 2, '.', ''),
                'qty' => $copies,
            ];

            if (in_array('qty', $columns, true)) {
                $rows[] = $payload;
            } else {
                for ($i = 0; $i < $copies; $i++) {
                    $rows[] = $payload;
                }
            }
        }

        if ($rows === []) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Direct (stock) products can be exported. Menu / KOT / BOT items are skipped.',
                ], 422);
            }

            return back()->with('error', 'Only Direct (stock) products can be exported.');
        }

        $csv = $this->buildCsv($columns, $rows);
        $saveServer = (bool) Setting::get('bartender_save_server', true);
        $downloadBrowser = (bool) Setting::get('bartender_download_browser', true);

        if ($saveServer) {
            $this->writeFixedExport($csv);
        }

        if (! $downloadBrowser && $saveServer) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'CSV saved to fixed URL',
                    'url' => url(self::EXPORT_RELATIVE),
                ]);
            }

            return back()->with('success', 'CSV saved to fixed URL: '.url(self::EXPORT_RELATIVE).' — run Print Barcodes on the label PC.');
        }

        $filename = 'barcode_labels_'.now()->format('Ymd_His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function downloadBatch()
    {
        $this->ensureSettings();
        $csvUrl = url(self::EXPORT_RELATIVE);
        $labelPath = (string) Setting::get('bartender_label_path', 'C:\\Labels\\BarcodePrint.btw');
        $labelPath = str_replace('/', '\\', $labelPath);

        $bat = <<<BAT
@echo off
echo Downloading latest barcode labels...
curl -o "C:\\Labels\\barcode_labels.csv" "{$csvUrl}"
if errorlevel 1 (
  echo Download failed. Check internet / server URL.
  pause
  exit /b 1
)
echo Done! Opening BarTender...
start "" "{$labelPath}"
BAT;

        return response($bat, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="update_and_print.bat"',
        ]);
    }

    protected function directProductsQuery(): Builder
    {
        return Product::query()->whereHas('category', function (Builder $q) {
            $q->where('type', 'direct');
        });
    }

    protected function buildCsv(array $columns, array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($fh, $line);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv ?: '';
    }

    protected function writeFixedExport(string $csv): void
    {
        $dir = public_path('exports');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($dir.DIRECTORY_SEPARATOR.'barcode_labels.csv', $csv);
        $deny = $dir.DIRECTORY_SEPARATOR.'index.html';
        if (! File::exists($deny)) {
            File::put($deny, '');
        }
    }

    protected function ensureSettings(): void
    {
        foreach ([
            'bartender_save_server' => ['1', 'boolean', 'Save barcode CSV to public/exports for BarTender batch download'],
            'bartender_download_browser' => ['1', 'boolean', 'Also download CSV in the browser on export'],
            'bartender_label_path' => ['C:\\Labels\\BarcodePrint.btw', 'string', 'BarTender .btw path on the print PC'],
            'bartender_show_open_btn' => ['0', 'boolean', 'Show Open BarTender hint on Print Labels page'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'bartender',
                    'description' => $desc,
                ]
            );
        }
    }
}
