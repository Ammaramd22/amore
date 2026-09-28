<?php

namespace App\Http\Controllers\Qr;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Services\KitchenTicketService;
use App\Services\OrderNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QrMenuController extends Controller
{
    public const THEMES = [
        'amber' => 'Amber Spice',
        'ocean' => 'Ocean Fresh',
        'luxe' => 'Night Luxe',
    ];

    public function index(RestaurantTable $table)
    {
        if (! (bool) Setting::get('qr_menu_enabled', true)) {
            abort(403, 'QR menu is disabled');
        }

        if (! $table->is_active) {
            abort(404);
        }

        $categories = Category::active()
            ->qrVisible()
            ->where(function ($q) {
                $q->whereNull('type')->orWhere('type', '!=', 'direct');
            })
            ->with(['products' => function ($q) {
                $q->available()->qrVisible();
            }])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function (Category $category) {
                $category->setRelation(
                    'products',
                    $category->products->values()
                );

                return $category;
            })
            ->filter(fn (Category $c) => $c->products->isNotEmpty())
            ->values();

        $productCatalog = $categories->flatMap(function (Category $c) {
            return $c->products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) ($p->final_price ?? $p->selling_price),
            ]);
        })->values();

        $theme = Setting::get('qr_menu_theme', 'amber');
        if (! array_key_exists($theme, self::THEMES)) {
            $theme = 'amber';
        }

        $settings = [
            'qr_show_prices' => (bool) Setting::get('qr_show_prices', true),
            'qr_order_approval' => (bool) Setting::get('qr_order_approval', true),
            'company_name' => Setting::get('company_name', 'Restaurant'),
            'company_phone' => Setting::get('company_phone', ''),
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'logo_url' => Setting::logoUrl(),
            'theme' => $theme,
            'theme_label' => self::THEMES[$theme],
        ];

        $verified = session($this->sessionKey($table), false);

        return view('qr.menu', compact('categories', 'table', 'settings', 'verified', 'productCatalog'));
    }

    public function verify(Request $request, RestaurantTable $table)
    {
        if (! (bool) Setting::get('qr_menu_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'QR menu is disabled'], 403);
        }

        $data = $request->validate([
            'code' => 'required|string|max:8',
        ]);

        $code = preg_replace('/\D+/', '', $data['code']);
        if (! $table->qr_code || $code !== $table->qr_code) {
            return response()->json(['success' => false, 'message' => 'Wrong table code. Check the card on your table.'], 422);
        }

        session([$this->sessionKey($table) => true]);

        return response()->json([
            'success' => true,
            'message' => 'Welcome — you can order for '.$table->name,
            'table' => $table->name,
        ]);
    }

    public function placeOrder(Request $request, RestaurantTable $table, KitchenTicketService $tickets)
    {
        if (! (bool) Setting::get('qr_menu_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'QR menu is disabled'], 403);
        }

        if (! session($this->sessionKey($table), false)) {
            return response()->json(['success' => false, 'message' => 'Enter the table code first'], 403);
        }

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1|max:50',
            'items.*.special_instructions' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'guest_name' => 'nullable|string|max:100',
        ]);

        $needsApproval = (bool) Setting::get('qr_order_approval', true);
        $taxRate = (bool) Setting::get('tax_enabled', false) ? (float) Setting::get('tax_rate', 0) : 0;
        $serviceRate = (bool) Setting::get('service_charge_enabled', false)
            ? (float) Setting::get('service_charge_rate', 0)
            : 0;

        DB::beginTransaction();
        try {
            // Serialize per-table so waiter/POS/QR cannot race into two open bills
            RestaurantTable::whereKey($table->id)->lockForUpdate()->first();

            $lineRows = [];
            $addedSubtotal = 0;

            foreach ($data['items'] as $item) {
                $product = Product::with('category')->findOrFail($item['product_id']);
                if (! $product->is_available || ! $product->show_in_qr) {
                    throw new \RuntimeException($product->name.' is not available');
                }
                if (($product->category?->type ?? 'kot') === 'direct') {
                    throw new \RuntimeException($product->name.' cannot be ordered from the QR menu');
                }
                $qty = (float) $item['quantity'];
                $unit = (float) ($product->final_price ?? $product->selling_price);
                $lineTotal = $unit * $qty;
                $addedSubtotal += $lineTotal;
                $lineRows[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'total_price' => $lineTotal,
                    'special_instructions' => $item['special_instructions'] ?? null,
                ];
            }

            $notes = $data['notes'] ?? null;
            if (! empty($data['guest_name'])) {
                $notes = trim(($notes ? $notes.' · ' : '').'Guest: '.$data['guest_name']);
            }

            $order = null;
            $isNew = true;
            $printJobs = [];

            if ($needsApproval) {
                // One pending QR request per table — stack items until waiter accepts (then merges into open bill)
                $order = Order::query()
                    ->awaitingQrApproval()
                    ->where('table_id', $table->id)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($order) {
                    $isNew = false;
                    if ($notes) {
                        $order->update([
                            'order_notes' => trim(($order->order_notes ? $order->order_notes.' · ' : '').$notes),
                        ]);
                    }
                } else {
                    $order = Order::create([
                        'order_number' => OrderNumberService::generate(
                            Setting::get('invoice_prefix', 'INV-'),
                            'orders',
                            (bool) Setting::get('invoice_reset_daily', true)
                        ),
                        'table_id' => $table->id,
                        'order_type' => 'dine_in',
                        'source' => 'qr',
                        'status' => 'pending',
                        'approval_status' => 'pending',
                        'payment_status' => 'unpaid',
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'service_charge' => 0,
                        'discount_amount' => 0,
                        'total_amount' => 0,
                        'paid_amount' => 0,
                        'order_notes' => $notes,
                    ]);
                }
            } else {
                // Auto-approve: always attach to the single open bill on this table
                $order = Order::query()
                    ->openBill()
                    ->where('table_id', $table->id)
                    ->where('order_type', 'dine_in')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($order) {
                    $isNew = false;
                    if ($notes) {
                        $order->update([
                            'order_notes' => trim(($order->order_notes ? $order->order_notes.' · ' : '').$notes),
                        ]);
                    }
                } else {
                    $order = Order::create([
                        'order_number' => OrderNumberService::generate(
                            Setting::get('invoice_prefix', 'INV-'),
                            'orders',
                            (bool) Setting::get('invoice_reset_daily', true)
                        ),
                        'table_id' => $table->id,
                        'order_type' => 'dine_in',
                        'source' => 'qr',
                        'status' => 'pending',
                        'approval_status' => 'approved',
                        'payment_status' => 'unpaid',
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'service_charge' => 0,
                        'discount_amount' => 0,
                        'total_amount' => 0,
                        'paid_amount' => 0,
                        'order_notes' => $notes,
                    ]);
                }
            }

            $createdItems = collect();
            foreach ($lineRows as $row) {
                $product = $row['product'];
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total_price' => $row['total_price'],
                    'special_instructions' => $row['special_instructions'],
                    'routed_to' => match ($product->category?->type ?? 'kot') {
                        'bot' => 'bar',
                        'direct' => 'direct',
                        default => 'kitchen',
                    },
                ]);
                $createdItems->push($orderItem);
            }

            $order->refresh();
            $subtotal = (float) $order->items()->sum('total_price');
            $afterDiscount = max(0, $subtotal - (float) $order->discount_amount);
            $tax = $afterDiscount * ($taxRate / 100);
            $service = $afterDiscount * ($serviceRate / 100);
            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'service_charge' => $service,
                'total_amount' => $afterDiscount + $tax + $service,
            ]);

            if (! $needsApproval) {
                $table->update(['status' => 'occupied']);
                $freshItems = $order->items()->with(['product.category.kitchen'])->whereIn('id', $createdItems->pluck('id'))->get();
                $printJobs = $tickets->createKitchenOrders($order, $freshItems);
                $tickets->deductStock($order, $freshItems);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'needs_approval' => $needsApproval,
                'added_to_existing' => ! $isNew,
                'print_jobs' => $printJobs,
                'message' => $needsApproval
                    ? ($isNew
                        ? 'Order sent to your waiter for confirmation'
                        : 'Items added — waiting for waiter confirmation')
                    : ($isNew
                        ? 'Order sent to the kitchen'
                        : 'Items added to your table bill — sent to kitchen'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QR place order failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function sessionKey(RestaurantTable $table): string
    {
        return 'qr_verified_table_'.$table->id;
    }
}
