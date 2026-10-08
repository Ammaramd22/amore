<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Kitchen;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AccountService;
use App\Services\ChequeService;
use App\Services\OrderNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('supplier', 'creator')->withCount('items');
        if ($request->filled('q')) {
            $q = '%'.$request->q.'%';
            $query->where(function ($inner) use ($q) {
                $inner->where('purchase_number', 'like', $q)
                    ->orWhere('invoice_number', 'like', $q)
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $q));
            });
        }
        if ($request->filled('supplier')) {
            $query->where('supplier_id', $request->supplier);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }
        $totalAmount = (clone $query)->sum('total_amount');
        $purchases = $query->latest()->paginate(20)->withQueryString();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $statuses = ['pending', 'received', 'partial', 'cancelled'];
        $paymentStatuses = ['unpaid', 'partial', 'paid'];

        return view('admin.purchases.index', compact('purchases', 'totalAmount', 'suppliers', 'statuses', 'paymentStatuses'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $ingredients = Ingredient::active()->orderBy('name')->get();

        // Avoid BelongsToMany column constraints (ambiguous id / missing pivot cols → 500).
        // Avoid lazy $product->suppliers when pivot table is missing.
        $productsQuery = Product::query()
            ->where(function ($q) {
                $q->where('track_stock', true);
                if (DB::getSchemaBuilder()->hasColumn('categories', 'type')) {
                    $q->orWhereHas('category', function ($c) {
                        $c->where('type', 'direct');
                    });
                }
            })
            ->active()
            ->orderBy('name');
        $products = $productsQuery->get(['id', 'name', 'stock_quantity', 'selling_price', 'category_id', 'track_stock', 'is_available']);

        $supplierIdsByProduct = [];
        if (DB::getSchemaBuilder()->hasTable('product_supplier') && $products->isNotEmpty()) {
            DB::table('product_supplier')
                ->whereIn('product_id', $products->pluck('id'))
                ->get(['product_id', 'supplier_id'])
                ->each(function ($row) use (&$supplierIdsByProduct) {
                    $supplierIdsByProduct[(int) $row->product_id][] = (int) $row->supplier_id;
                });
        }
        $categories = Category::active()->orderBy('name')->get();
        $subcategories = collect();
        $kitchens = Kitchen::active()->orderBy('name')->get();

        $lastPrices = collect();
        if (DB::getSchemaBuilder()->hasTable('purchase_items')) {
            PurchaseItem::query()
                ->orderByDesc('id')
                ->limit(2000)
                ->get(['id', 'ingredient_id', 'product_id', 'unit_price'])
                ->each(function ($item) use ($lastPrices) {
                    if ($item->ingredient_id) {
                        $key = 'ing_'.$item->ingredient_id;
                    } elseif ($item->product_id) {
                        $key = 'prod_'.$item->product_id;
                    } else {
                        return;
                    }
                    if (! $lastPrices->has($key)) {
                        $lastPrices->put($key, $item->unit_price);
                    }
                });
        }

        // Build JSON in PHP — Blade misparses nested [] inside @json(...map(fn...))
        $productsJson = $products->map(function (Product $product) use ($supplierIdsByProduct, $lastPrices) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => (float) ($product->stock_quantity ?? 0),
                'selling_price' => (string) ($product->selling_price ?? ''),
                'last_price' => (string) ($lastPrices['prod_'.$product->id] ?? ''),
                'supplier_ids' => array_values($supplierIdsByProduct[(int) $product->id] ?? []),
            ];
        })->values()->all();

        return view('admin.purchases.create', compact('suppliers', 'ingredients', 'products', 'productsJson', 'categories', 'subcategories', 'kitchens', 'lastPrices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:ingredient,product',
            'items.*.ingredient_id' => 'required_if:items.*.item_type,ingredient|exists:ingredients,id|nullable',
            'items.*.product_id' => 'required_if:items.*.item_type,product|exists:products,id|nullable',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,cheque',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'cheque_number' => 'nullable|string|max:100',
            'cheque_bank_name' => 'nullable|string|max:150',
            'cheque_branch_name' => 'nullable|string|max:150',
            'cheque_date' => 'nullable|date',
            'cheque_due_date' => 'nullable|date',
            'cheque_account_id' => 'nullable|exists:accounts,id',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_number' => OrderNumberService::generate('PUR-', 'purchases', false, null, 'purchase_number'),
                'invoice_number' => $data['invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $subtotal += $totalPrice;

                if ($item['item_type'] === 'product' && !empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product && ! $this->productAllowedForSupplier($product, (int) $data['supplier_id'])) {
                        throw new \RuntimeException('Product "'.$product->name.'" is not linked to the selected supplier.');
                    }
                    $before = $product->stock_quantity;
                    $product->increment('stock_quantity', $item['quantity']);
                    if (! empty($item['expiry_date'])) {
                        $product->expiry_date = $item['expiry_date'];
                        $product->save();
                    }

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $totalPrice,
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'purchase',
                        'quantity' => $item['quantity'],
                        'stock_before' => $before,
                        'stock_after' => $before + $item['quantity'],
                        'unit' => $product->unit ?? 'pcs',
                        'unit_cost' => $item['unit_price'],
                        'reference_id' => $purchase->id,
                        'reference_type' => Purchase::class,
                        'created_by' => auth()->id(),
                    ]);
                } else {
                    $ingredient = Ingredient::find($item['ingredient_id']);
                    $before = $ingredient->stock_quantity;
                    $ingredient->increment('stock_quantity', $item['quantity']);
                    if (! empty($item['expiry_date'])) {
                        $ingredient->expiry_date = $item['expiry_date'];
                        $ingredient->save();
                    }

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'ingredient_id' => $item['ingredient_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $totalPrice,
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);

                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'type' => 'purchase',
                        'quantity' => $item['quantity'],
                        'stock_before' => $before,
                        'stock_after' => $before + $item['quantity'],
                        'unit' => $ingredient->unit,
                        'unit_cost' => $item['unit_price'],
                        'reference_id' => $purchase->id,
                        'reference_type' => Purchase::class,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $paidAmount = 0;
            if (!empty($data['payment_amount']) && $data['payment_amount'] > 0) {
                $paidAmount = min($data['payment_amount'], $subtotal);
                $method = $data['payment_method'] ?? 'cash';
                $ref = $data['payment_reference'] ?? null;
                if ($method === 'cheque' && ! empty($data['cheque_number'])) {
                    $ref = $data['cheque_number'];
                }
                $supplierPayment = SupplierPayment::create([
                    'supplier_id' => $data['supplier_id'],
                    'purchase_id' => $purchase->id,
                    'method' => $method,
                    'amount' => $paidAmount,
                    'reference_number' => $ref,
                    'payment_date' => $data['payment_date'] ?? today(),
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                if ($method === 'cheque' && ChequeService::enabled()) {
                    if (empty($data['cheque_number']) || empty($data['cheque_due_date'])) {
                        throw new \RuntimeException('Cheque number and due date are required.');
                    }
                    app(ChequeService::class)->createFromPayment($supplierPayment, [
                        'cheque_number' => $data['cheque_number'],
                        'bank_name' => $data['cheque_bank_name'] ?? null,
                        'branch_name' => $data['cheque_branch_name'] ?? null,
                        'cheque_date' => $data['cheque_date'] ?? $data['payment_date'] ?? today(),
                        'due_date' => $data['cheque_due_date'],
                        'account_id' => $data['cheque_account_id'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);
                    // Ledger posts when cheque is cleared
                } else {
                    app(AccountService::class)->postSupplierPayment($supplierPayment->loadMissing('purchase'));
                }
            }

            $paymentStatus = 'unpaid';
            if ($paidAmount >= $subtotal) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            $purchase->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus,
                'status' => 'received',
            ]);

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('items.ingredient', 'supplier', 'payments');
        return view('admin.purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('items.product', 'items.ingredient');
        $suppliers = Supplier::active()->get();
        $ingredients = Ingredient::active()->get();
        $products = Product::where('track_stock', true)->orWhereHas('category', function ($q) {
            $q->where('type', 'direct');
        })->active()->get();
        return view('admin.purchases.edit', compact('purchase', 'suppliers', 'ingredients', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $purchase->update($request->only(['status', 'payment_status', 'notes']));
        return redirect()->route('purchases.index')->with('success', 'Purchase updated.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Purchase deleted.');
    }

    public function addPayment(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'method' => 'required|in:cash,card,bank_transfer,cheque',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'cheque_number' => 'nullable|string|max:100',
            'cheque_bank_name' => 'nullable|string|max:150',
            'cheque_branch_name' => 'nullable|string|max:150',
            'cheque_date' => 'nullable|date',
            'cheque_due_date' => 'nullable|date',
            'cheque_account_id' => 'nullable|exists:accounts,id',
        ]);

        $balance = max(0, $purchase->total_amount - $purchase->paid_amount);
        $amount = min($data['amount'], $balance + 0.01);

        if ($data['method'] === 'cheque' && ChequeService::enabled()) {
            if (empty($data['cheque_number']) || empty($data['cheque_due_date'])) {
                return back()->withErrors(['error' => 'Cheque number and due date are required.'])->withInput();
            }
        }

        $ref = $data['reference_number'] ?? null;
        if ($data['method'] === 'cheque' && ! empty($data['cheque_number'])) {
            $ref = $data['cheque_number'];
        }

        $supplierPayment = SupplierPayment::create([
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'method' => $data['method'],
            'amount' => $amount,
            'reference_number' => $ref,
            'payment_date' => $data['payment_date'],
            'notes' => null,
            'created_by' => auth()->id(),
        ]);

        if ($data['method'] === 'cheque' && ChequeService::enabled()) {
            app(ChequeService::class)->createFromPayment($supplierPayment, [
                'cheque_number' => $data['cheque_number'],
                'bank_name' => $data['cheque_bank_name'] ?? null,
                'branch_name' => $data['cheque_branch_name'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? $data['payment_date'],
                'due_date' => $data['cheque_due_date'],
                'account_id' => $data['cheque_account_id'] ?? null,
            ]);
        } else {
            app(AccountService::class)->postSupplierPayment($supplierPayment->loadMissing('purchase'));
        }

        $newPaid = $purchase->paid_amount + $amount;
        $paymentStatus = 'unpaid';
        if ($newPaid >= $purchase->total_amount) {
            $paymentStatus = 'paid';
        } elseif ($newPaid > 0) {
            $paymentStatus = 'partial';
        }

        $purchase->update(['paid_amount' => $newPaid, 'payment_status' => $paymentStatus]);
        return redirect()->route('purchases.index')->with('success', 'Payment added.');
    }

    protected function productAllowedForSupplier(Product $product, int $supplierId): bool
    {
        if (! DB::getSchemaBuilder()->hasTable('product_supplier')) {
            return true;
        }

        return DB::table('product_supplier')
            ->where('product_id', $product->id)
            ->where('supplier_id', $supplierId)
            ->exists();
    }
}
