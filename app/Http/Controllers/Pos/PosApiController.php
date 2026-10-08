<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\HeldOrder;
use App\Models\Ingredient;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AccountService;
use App\Services\KitchenTicketService;
use App\Services\LoyaltyService;
use App\Services\NetworkPrinterService;
use App\Services\OrderNumberService;
use App\Services\StockService;
use App\Services\WaiterReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PosApiController extends Controller
{
    public function categories()
    {
        $categories = \App\Models\Category::active()
            ->posVisible()
            ->with([
                'products' => fn ($q) => $q->available()->posVisible()->limit(1),
                'subcategories' => fn ($q) => $q->active()->orderBy('display_order')->orderBy('name'),
            ])
            ->get()
            ->filter(fn ($c) => $c->products->count() > 0)
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'subcategories' => $c->subcategories->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->values(),
            ]);

        return response()->json(['categories' => $categories]);
    }

    public function products(Request $request)
    {
        $q = $request->get('q');
        $categoryId = $request->get('category_id');
        $id = $request->get('id');
        $withExtras = $id || $request->boolean('extras');

        $query = Product::query()
            ->available()
            ->posVisible()
            ->when($id, fn ($query) => $query->where('id', $id))
            ->when($q, fn ($query) => $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            }))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId));

        if ($withExtras) {
            $query->with([
                'variants' => fn ($vq) => $vq->active()->orderBy('name'),
                'sharedAddons' => fn ($aq) => $aq->active()->ordered(),
                'addons' => fn ($aq) => $aq->active()->orderBy('name'),
                'addonGroups' => fn ($gq) => $gq->active()->ordered()->with([
                    'addons' => fn ($aq) => $aq->ordered(),
                    'branches:id',
                ]),
                'optionSets' => fn ($oq) => $oq->active()->ordered()->with(['options' => fn ($opt) => $opt->active()->ordered()]),
            ]);
        } else {
            $query->with([
                'variants' => fn ($vq) => $vq->active()->orderBy('name'),
            ])->withCount([
                'addons as pos_addons_count' => fn ($aq) => $aq->active(),
                'sharedAddons as pos_shared_addons_count' => fn ($aq) => $aq->active(),
                'addonGroups as pos_addon_groups_count' => fn ($gq) => $gq->active(),
                'optionSets as pos_option_sets_count' => fn ($oq) => $oq->active(),
            ]);
        }

        $products = $query->get()->map(function ($p) use ($withExtras) {
            $basePrice = (float) ($p->final_price ?? $p->selling_price ?? $p->price ?? 0);
            $variants = $p->variants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'price_adjustment' => (float) $v->price_adjustment,
                'final_price' => $basePrice + (float) $v->price_adjustment,
            ])->values();

            if (! $withExtras) {
                $hasAddons = (int) ($p->pos_addons_count ?? 0) > 0
                    || (int) ($p->pos_shared_addons_count ?? 0) > 0
                    || (int) ($p->pos_addon_groups_count ?? 0) > 0
                    || (int) ($p->pos_option_sets_count ?? 0) > 0
                    || (bool) $p->has_addons;

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $basePrice,
                    'category_id' => $p->category_id,
                    'subcategory_id' => $p->subcategory_id,
                    'image' => $p->imageUrl(),
                    'has_variants' => $variants->isNotEmpty() || (bool) $p->has_variants,
                    'has_addons' => $hasAddons,
                    'variants' => $variants,
                    'addons' => [],
                    'modifier_sets' => [],
                    'option_sets' => [],
                ];
            }

            $resolvedAddons = $p->posAddons();
            $addons = $resolvedAddons->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'price' => (float) $a->price,
                'shared' => $a instanceof \App\Models\Addon,
            ])->values();

            $modifierSets = $p->posModifierSets()->map(fn ($set) => [
                'id' => $set['id'],
                'name' => $set['name'],
                'display_name' => $set['display_name'],
                'require_selection' => $set['require_selection'],
                'allow_multiple' => $set['allow_multiple'],
                'hide_on_receipt' => $set['hide_on_receipt'],
                'addons' => $set['addons']->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'price' => (float) $a->price,
                    'shared' => true,
                    'group_id' => $set['id'],
                    'is_preselected' => (bool) ($a->pivot->is_preselected ?? false),
                    'hide_on_receipt' => $set['hide_on_receipt'],
                ])->values(),
            ])->values();

            $optionSets = $p->posOptionSets()->map(fn ($set) => [
                'id' => $set['id'],
                'name' => $set['name'],
                'display_name' => $set['display_name'],
                'type' => $set['type'],
                'require_selection' => $set['require_selection'],
                'options' => $set['options']->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'color' => $o->color,
                    'option_set_id' => $set['id'],
                    'option_set_name' => $set['display_name'],
                ])->values(),
            ])->values();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $basePrice,
                'category_id' => $p->category_id,
                'subcategory_id' => $p->subcategory_id,
                'image' => $p->imageUrl(),
                'has_variants' => $variants->isNotEmpty() || (bool) $p->has_variants,
                'has_addons' => $addons->isNotEmpty() || $optionSets->isNotEmpty() || $modifierSets->isNotEmpty() || (bool) $p->has_addons,
                'variants' => $variants,
                'addons' => $addons,
                'modifier_sets' => $modifierSets,
                'option_sets' => $optionSets,
            ];
        });

        return response()->json(['products' => $products]);
    }

    public function tables()
    {
        $tables = RestaurantTable::with('floor')
            ->active()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'floor' => $t->floor?->name,
                'status' => $t->status,
                'capacity' => $t->capacity,
            ]);
        return response()->json($tables);
    }

    public function customers(Request $request)
    {
        $q = $request->get('q');
        $customers = Customer::active()
            ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"))
            ->limit(20)
            ->get()
            ->map(function (Customer $c) {
                $row = [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'address' => $c->address,
                ];
                if (LoyaltyService::enabled()) {
                    $row['loyalty'] = app(LoyaltyService::class)->customerPayload($c);
                }

                return $row;
            });

        return response()->json($customers);
    }

    public function loyaltyLookup(Request $request)
    {
        if (! LoyaltyService::enabled()) {
            return response()->json(['success' => false, 'message' => 'Loyalty disabled'], 403);
        }
        $token = (string) $request->get('token', $request->get('q', ''));
        $customer = app(LoyaltyService::class)->findByToken($token);
        if (! $customer && $request->filled('phone')) {
            $customer = Customer::active()->where('phone', $request->phone)->first();
        }
        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Customer / stamp card not found'], 404);
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'loyalty' => app(LoyaltyService::class)->customerPayload($customer),
            ],
        ]);
    }

    public function loyaltyEnroll(Customer $customer)
    {
        if (! LoyaltyService::enabled()) {
            return response()->json(['success' => false, 'message' => 'Loyalty disabled'], 403);
        }
        $customer = app(LoyaltyService::class)->enroll($customer);

        return response()->json([
            'success' => true,
            'message' => 'Customer joined stamp card',
            'loyalty' => app(LoyaltyService::class)->customerPayload($customer),
            'card_url' => app(LoyaltyService::class)->cardUrl($customer),
        ]);
    }

    public function loyaltyRedeem(Customer $customer)
    {
        if (! LoyaltyService::enabled()) {
            return response()->json(['success' => false, 'message' => 'Loyalty disabled'], 403);
        }
        try {
            $loyalty = app(LoyaltyService::class)->redeemFree($customer);

            return response()->json([
                'success' => true,
                'message' => 'Free drink redeemed — give complimentary '.LoyaltyService::rewardLabel(),
                'loyalty' => $loyalty,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function loyaltyStatus(Customer $customer)
    {
        if (! LoyaltyService::enabled()) {
            return response()->json(['success' => false, 'message' => 'Loyalty disabled'], 403);
        }

        return response()->json([
            'success' => true,
            'loyalty' => app(LoyaltyService::class)->customerPayload($customer),
        ]);
    }

    public function quickAddCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'join_loyalty' => 'nullable|boolean',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);

        $loyalty = null;
        $cardUrl = null;
        if (! empty($validated['join_loyalty']) && LoyaltyService::enabled()) {
            $loyaltyService = app(LoyaltyService::class);
            $customer = $loyaltyService->enroll($customer);
            $loyalty = $loyaltyService->customerPayload($customer);
            $cardUrl = $loyaltyService->cardUrl($customer);
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'loyalty' => $loyalty,
            ],
            'loyalty' => $loyalty,
            'card_url' => $cardUrl,
        ]);
    }

    public function updateCustomerAddress(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:500',
        ]);

        $customer->update(['address' => trim($validated['address'])]);

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
            ],
        ]);
    }

    public function holdOrder(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'order_type' => 'required|string',
            'table_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $subtotal = collect($data['items'])->sum(fn($item) => ($item['price'] + collect($item['addons'] ?? [])->sum('price')) * $item['quantity']);

        // Only dine-in orders may keep a table assignment
        if (($data['order_type'] ?? '') !== 'dine_in') {
            $data['table_id'] = null;
        }
        $this->applyTakeawayCustomerFields($data);

        $held = HeldOrder::create([
            'hold_reference' => 'HOLD-' . now()->format('Ymd-His') . '-' . auth()->id(),
            'branch_id' => \App\Services\BranchService::currentId(),
            'table_id' => $data['table_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'user_id' => auth()->id(),
            'order_type' => $data['order_type'],
            'items' => $data['items'],
            'subtotal' => $subtotal,
            'total_amount' => $subtotal,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['success' => true, 'held_order' => $held]);
    }

    public function heldOrders()
    {
        $orders = HeldOrder::with(['table', 'customer'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
        return response()->json(['orders' => $orders]);
    }

    public function recallOrder(Request $request)
    {
        $held = HeldOrder::with('customer')->findOrFail($request->input('id'));
        $items = $held->items;
        $payload = [
            'success' => true,
            'items' => $items,
            'order_type' => $held->order_type,
            'customer_id' => $held->customer_id,
            'customer_name' => $held->customer?->name,
            'customer_phone' => $held->customer?->phone,
        ];
        $held->delete();

        return response()->json($payload);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'order_type' => 'required|in:dine_in,takeaway,delivery,express',
            'table_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'items' => 'required|array',
            'items.*.product_id' => 'nullable|integer',
            'items.*.is_custom_item' => 'nullable|boolean',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric',
            'items.*.addons' => 'nullable|array',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.variant_name' => 'nullable|string',
            'discount_amount' => 'nullable|numeric',
            'discount_type' => 'nullable|in:fixed,percentage',
            'tax_rate' => 'nullable|numeric',
            'service_charge' => 'nullable|numeric',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,online,credit,split',
            'payments' => 'nullable|array|min:1',
            'payments.*.method' => 'required_with:payments|in:cash,card,bank_transfer,online,credit',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payment_notes' => 'nullable|string',
            'cash_received' => 'nullable|numeric',
            'order_notes' => 'nullable|string',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_partner_id' => 'nullable|integer|exists:delivery_partners,id',
            'waiter_id' => 'nullable|integer|exists:users,id',
            'payment_on_delivery' => 'nullable|boolean',
            'loyalty_redeem' => 'nullable|boolean',
            'loyalty_redeem_value' => 'nullable|numeric|min:0',
            'items.*.loyalty_free' => 'nullable|boolean',
            'items.*.special_instructions' => 'nullable|string|max:500',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.is_comp' => 'nullable|boolean',
            'items.*.comp_reason' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
        ]);

        $this->assertPosCartItems($data['items']);

        $isCod = ($data['order_type'] ?? null) === 'delivery' && $request->boolean('payment_on_delivery');

        // Only dine-in orders may keep a table assignment
        if (($data['order_type'] ?? '') !== 'dine_in') {
            $data['table_id'] = null;
        }
        $this->applyTakeawayCustomerFields($data);

        if (! $isCod && empty($data['payments']) && empty($data['payment_method'])) {
            return response()->json(['success' => false, 'message' => 'Payment method required'], 422);
        }

        if (($data['order_type'] ?? null) === 'delivery') {
            $partnerId = ! empty($data['delivery_partner_id']) ? (int) $data['delivery_partner_id'] : null;
            $partner = $partnerId ? \App\Models\DeliveryPartner::find($partnerId) : null;
            $marketplace = $partner && $partner->settlesLater();

            // Marketplace apps (Uber / PickMe / Buyit): no customer or address required
            if (! $marketplace) {
                if (empty($data['customer_id'])) {
                    return response()->json(['success' => false, 'message' => 'Select a customer for own delivery'], 422);
                }
                if (empty(trim((string) ($data['delivery_address'] ?? '')))) {
                    return response()->json(['success' => false, 'message' => 'Enter delivery address'], 422);
                }
            } elseif (empty(trim((string) ($data['delivery_address'] ?? '')))) {
                $data['delivery_address'] = 'Via '.($partner->name ?? 'delivery partner');
            }
        }

        // Dine-in: cannot start a second bill while the table already has an unpaid open order
        if (($data['order_type'] ?? null) === 'dine_in' && ! empty($data['table_id'])) {
            $open = Order::findOpenBillForTable((int) $data['table_id']);
            if ($open) {
                return response()->json([
                    'success' => false,
                    'message' => $open->order_number.' is already open on this table. Open that bill to pay or add items — one bill per table.',
                    'existing_order_id' => $open->id,
                    'existing_order_number' => $open->order_number,
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $subtotal = collect($data['items'])->sum(fn ($item) => $this->posLineNet($item));

            $discount = $data['discount_type'] === 'percentage'
                ? $subtotal * ($data['discount_amount'] / 100)
                : ($data['discount_amount'] ?? 0);

            $afterDiscount = max(0, $subtotal - $discount);
            $taxRate = (bool) Setting::get('tax_enabled', false) ? ($data['tax_rate'] ?? Setting::get('tax_rate', 0)) : 0;
            $tax = $afterDiscount * ($taxRate / 100);
            $serviceCharge = $afterDiscount * (($data['service_charge'] ?? 0) / 100);
            $total = $afterDiscount + $tax + $serviceCharge;
            $rounded = $this->applyPriceRounding($total);
            $total = $rounded['total'];
            $roundingAmount = $rounded['rounding_amount'];

            $paymentLines = [];
            $paidSum = 0.0;
            $change = 0.0;
            $paymentStatus = 'paid';
            $cardSurchargeAmount = 0.0;

            if ($isCod) {
                $paymentStatus = 'unpaid';
            } else {
                $paymentLines = $this->normalizePaymentLines($data, $total);
                $paidBase = round(collect($paymentLines)->sum('amount'), 2);
                if ($paidBase + 0.009 < $total) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payments (' . number_format($paidBase, 2) . ') are less than total (' . number_format($total, 2) . ')',
                    ], 422);
                }
                $surcharged = $this->applyCardSurchargeToLines($paymentLines);
                $paymentLines = $surcharged['lines'];
                $cardSurchargeAmount = $surcharged['surcharge'];
                $paidSum = round(collect($paymentLines)->sum('amount'), 2);
                $payable = round($total + $cardSurchargeAmount, 2);
                $change = max(0, $paidSum - $payable);
            }

            // Get current register
            $register = CashRegister::getActiveForPos(auth()->id());

            $order = Order::create([
                'order_number' => OrderNumberService::generate(Setting::get('invoice_prefix', 'INV-'), 'orders', (bool) Setting::get('invoice_reset_daily', true)),
                'branch_id' => \App\Services\BranchService::currentId(),
                'table_id' => $data['table_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'waiter_id' => $this->resolveWaiterId($data['waiter_id'] ?? null),
                'cashier_id' => auth()->id(),
                'register_id' => $register?->id,
                'order_type' => $data['order_type'],
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'service_charge' => $serviceCharge,
                'discount_amount' => $discount,
                'rounding_amount' => $roundingAmount,
                'card_surcharge_amount' => $cardSurchargeAmount,
                'total_amount' => $total,
                'paid_amount' => $paidSum,
                'change_amount' => $change,
                'order_notes' => $data['order_notes'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_partner_id' => $data['delivery_partner_id'] ?? null,
                'delivery_status' => $data['order_type'] === 'delivery' ? 'pending' : null,
                'payment_on_delivery' => $isCod,
                'is_comp' => collect($data['items'])->contains(fn ($i) => ! empty($i['is_comp'])),
            ]);

            $deliveryAddress = trim((string) ($data['delivery_address'] ?? ''));
            if ($order->order_type === 'delivery' && $deliveryAddress !== '' && ! empty($data['customer_id'])) {
                Customer::where('id', $data['customer_id'])->update(['address' => $deliveryAddress]);
            }

            if (! $isCod) {
                $this->recordPaymentLines($order, $paymentLines, $register, $data['payment_notes'] ?? null);
                if ($register) {
                    $register->increment('orders_count');
                }
            } elseif ($register) {
                $register->increment('orders_count');
            }

            foreach ($data['items'] as $item) {
                $this->createOrderItemFromPosPayload($order, $item, true);
            }

            if (! empty($data['table_id'])) {
                RestaurantTable::syncOccupancy((int) $data['table_id']);
            }

            $printJobs = $this->createKitchenOrders($order);
            $this->deductStock($order);

            DB::commit();

            $loyalty = null;
            if ($order->payment_status === 'paid' && ! empty($order->customer_id)) {
                $loyaltyService = app(LoyaltyService::class);
                $fresh = $order->fresh(['customer', 'items.product']);
                if ($request->boolean('loyalty_redeem') && $fresh->customer) {
                    try {
                        $loyalty = $loyaltyService->redeemFree(
                            $fresh->customer,
                            $fresh,
                            isset($data['loyalty_redeem_value']) ? (float) $data['loyalty_redeem_value'] : null
                        );
                    } catch (\Throwable $e) {
                        // Redeem failed — still award stamps if possible
                        Log::warning('Loyalty redeem failed on checkout: '.$e->getMessage());
                    }
                }
                // Free lines (price 0) are already skipped in award; only skip extra qty for discount-style redeem
                $hasFreeLine = collect($data['items'] ?? [])->contains(fn ($i) => ! empty($i['loyalty_free']));
                $skipQty = ($request->boolean('loyalty_redeem') && ! $hasFreeLine) ? 1 : 0;
                $earned = $loyaltyService->awardFromOrder($fresh, $skipQty);
                if ($earned) {
                    $loyalty = array_merge($loyalty ?? [], $earned);
                }
            }

            if ($order->order_type === 'delivery' && $order->delivery_partner_id) {
                try {
                    app(\App\Services\DeliveryPartnerLedgerService::class)->recordOrderDue($order->fresh(['deliveryPartner']));
                } catch (\Throwable $e) {
                    Log::warning('Partner ledger due failed: '.$e->getMessage());
                }
            }

            $order->loadMissing('deliveryPartner');

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'change_amount' => (float) $order->change_amount,
                'payment_on_delivery' => (bool) $order->payment_on_delivery,
                'payment_status' => $order->payment_status,
                'print_url' => route('pos.print-receipt', $order),
                // Dine-in/takeaway Pay Now: no auto KOT. Delivery (any pay mode) + COD: return jobs for direct print.
                'print_jobs' => ($isCod || ($data['order_type'] ?? '') === 'delivery') ? $printJobs : [],
                'loyalty' => $loyalty,
                'message' => $isCod
                    ? (
                        (($order->deliveryPartner?->collection_type ?? 'partner') === 'partner')
                            ? 'Partner order placed — amount due on Partner Ledger (no POS payment)'
                            : 'COD delivery order placed — collect cash on delivery'
                    )
                    : 'Order saved!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS checkout failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'order_type' => 'nullable|in:dine_in,takeaway,express',
            'table_id' => 'nullable|integer|exists:tables,id',
            'customer_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.is_custom_item' => 'nullable|boolean',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric',
            'items.*.addons' => 'nullable|array',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.variant_name' => 'nullable|string',
            'discount_amount' => 'nullable|numeric',
            'discount_type' => 'nullable|in:fixed,percentage',
            'tax_rate' => 'nullable|numeric',
            'service_charge' => 'nullable|numeric',
            'order_notes' => 'nullable|string',
            'waiter_id' => 'nullable|integer|exists:users,id',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.special_instructions' => 'nullable|string|max:500',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.is_comp' => 'nullable|boolean',
            'items.*.comp_reason' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
        ]);

        $this->assertPosCartItems($data['items']);

        $orderType = $data['order_type'] ?? 'dine_in';
        // Only dine-in orders may keep a table assignment
        if ($orderType !== 'dine_in') {
            $data['table_id'] = null;
        }
        $this->applyTakeawayCustomerFields($data);
        if ($orderType === 'dine_in' && empty($data['table_id'])) {
            return response()->json(['success' => false, 'message' => 'Please select a table for dine-in'], 422);
        }

        DB::beginTransaction();
        try {
            $taxRate = (bool) Setting::get('tax_enabled', false) ? ($data['tax_rate'] ?? Setting::get('tax_rate', 0)) : 0;
            $serviceRate = (float) ($data['service_charge'] ?? 0);

            // One open unpaid bill per dine-in table — add to existing instead of a second invoice
            $order = null;
            $isNew = true;
            if ($orderType === 'dine_in' && ! empty($data['table_id'])) {
                $order = Order::query()
                    ->openBill()
                    ->where('table_id', $data['table_id'])
                    ->where('order_type', 'dine_in')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();
                if ($order) {
                    $isNew = false;
                    $updates = [
                        'waiter_id' => $this->resolveWaiterId($data['waiter_id'] ?? null) ?: $order->waiter_id,
                        'order_notes' => $data['order_notes'] ?? $order->order_notes,
                    ];
                    if (! empty($data['customer_id'])) {
                        $updates['customer_id'] = $data['customer_id'];
                    }
                    $order->update($updates);
                }
            }

            if (! $order) {
                $subtotal = collect($data['items'])->sum(fn ($item) => $this->posLineNet($item));

                $discount = ($data['discount_type'] ?? 'fixed') === 'percentage'
                    ? $subtotal * (($data['discount_amount'] ?? 0) / 100)
                    : ($data['discount_amount'] ?? 0);

                $afterDiscount = max(0, $subtotal - $discount);
                $tax = $afterDiscount * ($taxRate / 100);
                $serviceCharge = $afterDiscount * ($serviceRate / 100);
                $total = $afterDiscount + $tax + $serviceCharge;
                $rounded = $this->applyPriceRounding($total);

                $order = Order::create([
                    'order_number' => OrderNumberService::generate(Setting::get('invoice_prefix', 'INV-'), 'orders', (bool) Setting::get('invoice_reset_daily', true)),
                    'branch_id' => \App\Services\BranchService::currentId(),
                    'table_id' => $data['table_id'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'waiter_id' => $this->resolveWaiterId($data['waiter_id'] ?? null),
                    'cashier_id' => null,
                    'order_type' => $orderType,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'service_charge' => $serviceCharge,
                    'discount_amount' => $discount,
                    'rounding_amount' => $rounded['rounding_amount'],
                    'total_amount' => $rounded['total'],
                    'paid_amount' => 0,
                    'order_notes' => $data['order_notes'] ?? null,
                    'is_comp' => collect($data['items'])->contains(fn ($i) => ! empty($i['is_comp'])),
                ]);
            }

            $newItems = collect();
            foreach ($data['items'] as $item) {
                $newItems->push($this->createOrderItemFromPosPayload($order, $item, false));
            }

            if (! $isNew) {
                $order->refresh();
                $subtotal = $order->items()->where('is_void', false)->sum('total_price');
                $afterDiscount = max(0, $subtotal - (float) $order->discount_amount);
                $tax = $afterDiscount * ($taxRate / 100);
                $serviceCharge = $afterDiscount * ($serviceRate / 100);
                $rounded = $this->applyPriceRounding($afterDiscount + $tax + $serviceCharge);
                $order->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'service_charge' => $serviceCharge,
                    'rounding_amount' => $rounded['rounding_amount'],
                    'total_amount' => $rounded['total'],
                    'status' => 'pending',
                    'is_comp' => $order->items()->where('is_void', false)->where('is_comp', true)->exists(),
                ]);
            }

            if (! empty($data['table_id'])) {
                RestaurantTable::syncOccupancy((int) $data['table_id']);
            }

            $newItems = $order->items()->with(['product.category.kitchen'])->whereIn('id', $newItems->pluck('id'))->get();
            $printJobs = $this->createKitchenOrders($order, $newItems);
            $this->deductStock($order, $newItems);

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_order' => $isNew,
                'print_kot_url' => route('pos.print-kot', $order),
                'print_jobs' => $printJobs,
                'message' => $isNew ? 'Order placed' : 'Items added to existing bill '.$order->order_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS place order failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function billingOrders()
    {
        $orders = Order::with(['customer', 'table.floor', 'items', 'kitchenOrders', 'waiter', 'deliveryPartner'])
            ->whereIn('order_type', ['dine_in', 'takeaway', 'express', 'delivery'])
            ->openBill()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $kots = $order->kitchenOrders->where('status', '!=', 'cancelled');
                $kitchenTickets = $kots->where('type', 'kitchen');
                $kotUnprinted = $kitchenTickets->filter(fn ($k) => empty($k->printed_at))->values();
                $kotPrinted = $kitchenTickets->filter(fn ($k) => ! empty($k->printed_at))->values();
                $kitchenSummary = [
                    'pending' => $kots->where('status', 'pending')->count(),
                    'preparing' => $kots->where('status', 'preparing')->count(),
                    'ready' => $kots->where('status', 'ready')->count(),
                    'served' => $kots->where('status', 'served')->count(),
                ];
                $kitchenLabel = 'No KOT';
                if ($kitchenSummary['ready'] > 0) {
                    $kitchenLabel = $kitchenSummary['ready'] . ' Ready';
                } elseif ($kitchenSummary['preparing'] > 0) {
                    $kitchenLabel = 'Preparing';
                } elseif ($kitchenSummary['pending'] > 0) {
                    $kitchenLabel = 'Pending kitchen';
                } elseif ($kitchenSummary['served'] > 0 && ($kitchenSummary['pending'] + $kitchenSummary['preparing'] + $kitchenSummary['ready']) === 0) {
                    $kitchenLabel = 'Served';
                }

                $partnerName = $order->deliveryPartner?->name;
                $isDelivery = $order->order_type === 'delivery';

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'status' => $order->status,
                    'delivery_status' => $order->delivery_status,
                    'payment_on_delivery' => (bool) $order->payment_on_delivery,
                    'delivery_address' => $order->delivery_address,
                    'delivery_partner_id' => $order->delivery_partner_id,
                    'delivery_partner_name' => $partnerName,
                    'customer' => $order->customer?->name
                        ?? ($isDelivery && $partnerName ? $partnerName : 'Walk-in'),
                    'waiter_name' => $order->waiter?->name,
                    'table_id' => $order->table_id,
                    'table_name' => $order->table?->name
                        ?? match ($order->order_type) {
                            'takeaway' => 'Takeaway',
                            'express' => 'Express',
                            'delivery' => $partnerName ?: 'Delivery',
                            default => '-',
                        },
                    'floor_name' => $order->table?->floor?->name
                        ?? ($isDelivery ? ($order->delivery_address ?: 'Delivery') : ''),
                    'table_capacity' => $order->table?->capacity,
                    'total' => (float) $order->total_amount,
                    'items_count' => $order->items->where('is_void', false)->count(),
                    'elapsed' => $order->created_at->diffForHumans(),
                    'kitchen' => $kitchenSummary,
                    'kitchen_label' => $kitchenLabel,
                    'kitchen_ready' => $kitchenSummary['ready'] > 0,
                    'kitchen_served' => $kitchenSummary['served'] > 0
                        && $kitchenSummary['ready'] === 0
                        && $kitchenSummary['preparing'] === 0
                        && $kitchenSummary['pending'] === 0,
                    'kot_count' => $kitchenTickets->count(),
                    'kot_unprinted_count' => $kotUnprinted->count(),
                    'kot_printed_count' => $kotPrinted->count(),
                    'kot_all_printed' => $kitchenTickets->isNotEmpty() && $kotUnprinted->isEmpty(),
                    'latest_kot_id' => optional($kitchenTickets->sortByDesc('id')->first())->id,
                    'unprinted_kot_ids' => $kotUnprinted->pluck('id')->values(),
                    'kot_ids' => $kitchenTickets->pluck('id')->values(),
                ];
            });

        return response()->json([
            'orders' => $orders,
            'sound_enabled' => (bool) Setting::get('pos_ready_sound_alert', true),
        ]);
    }

    public function tableOpenOrder(RestaurantTable $table)
    {
        $order = Order::findOpenBillForTable($table->id);

        return response()->json([
            'table_id' => $table->id,
            'table_name' => $table->name,
            'order' => $order ? [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
                'payment_status' => $order->payment_status,
            ] : null,
        ]);
    }

    public function settleOrder(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_method' => 'nullable|in:cash,card,bank_transfer,online,credit,split',
            'payments' => 'nullable|array|min:1',
            'payments.*.method' => 'required_with:payments|in:cash,card,bank_transfer,online,credit',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payment_notes' => 'nullable|string',
            'cash_received' => 'nullable|numeric',
            'waiter_id' => 'nullable|integer|exists:users,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'loyalty_redeem' => 'nullable|boolean',
            'loyalty_redeem_value' => 'nullable|numeric|min:0',
        ]);

        if (empty($data['payments']) && empty($data['payment_method'])) {
            return response()->json(['success' => false, 'message' => 'Payment method required'], 422);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Order is already paid'], 422);
        }

        DB::beginTransaction();
        try {
            if ($order->order_type === 'takeaway' && ($request->exists('customer_name') || $request->exists('customer_phone'))) {
                $order->customer_id = $this->resolveTakeawayCustomerId(
                    $data['customer_name'] ?? null,
                    $data['customer_phone'] ?? null,
                    $data['customer_id'] ?? $order->customer_id
                );
                $order->waiter_id = null;
                $order->table_id = null;
                $order->save();
            }

            // Apply free drink as bill discount before taking payment (open bills)
            if ($request->boolean('loyalty_redeem')) {
                $redeemValue = round((float) ($data['loyalty_redeem_value'] ?? 0), 2);
                if ($redeemValue <= 0) {
                    // Infer from cheapest qualifying paid item
                    $order->loadMissing('items.product');
                    $cats = LoyaltyService::categoryIds();
                    foreach ($order->items as $oi) {
                        if ($oi->is_void || (float) $oi->unit_price <= 0) {
                            continue;
                        }
                        $catId = (int) ($oi->product?->category_id ?? 0);
                        if ($cats && ! in_array($catId, $cats, true)) {
                            continue;
                        }
                        $unit = (float) $oi->unit_price;
                        $redeemValue = $unit;
                        break;
                    }
                }
                if ($redeemValue > 0) {
                    $subtotal = (float) $order->subtotal;
                    $newDiscount = min($subtotal, round((float) $order->discount_amount + $redeemValue, 2));
                    $after = max(0, $subtotal - $newDiscount);
                    $tax = Setting::get('tax_enabled', true)
                        ? round($after * ((float) Setting::get('tax_rate', 0) / 100), 2)
                        : 0.0;
                    $service = Setting::get('service_charge_enabled', false)
                        ? round($after * ((float) Setting::get('service_charge_rate', 0) / 100), 2)
                        : 0.0;
                    $rounded = $this->applyPriceRounding($after + $tax + $service);
                    $order->update([
                        'discount_amount' => $newDiscount,
                        'tax_amount' => $tax,
                        'service_charge' => $service,
                        'rounding_amount' => $rounded['rounding_amount'],
                        'total_amount' => $rounded['total'],
                        'order_notes' => trim(($order->order_notes ? $order->order_notes."\n" : '').'Loyalty: '.LoyaltyService::rewardLabel().' (−'.number_format($redeemValue, 2).')'),
                    ]);
                    $order->refresh();
                    $data['loyalty_redeem_value'] = $redeemValue;
                }
            }

            $total = (float) $order->total_amount;
            $paymentLines = $this->normalizePaymentLines($data, $total);
            $paidBase = round(collect($paymentLines)->sum('amount'), 2);
            if ($paidBase + 0.009 < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payments (' . number_format($paidBase, 2) . ') are less than total (' . number_format($total, 2) . ')',
                ], 422);
            }

            $surcharged = $this->applyCardSurchargeToLines($paymentLines);
            $paymentLines = $surcharged['lines'];
            $cardSurchargeAmount = $surcharged['surcharge'];
            $paidSum = round(collect($paymentLines)->sum('amount'), 2);
            $payable = round($total + $cardSurchargeAmount, 2);

            $register = CashRegister::getActiveForPos(auth()->id());
            $change = max(0, $paidSum - $payable);

            $updates = [
                'payment_status' => 'paid',
                'paid_amount' => $paidSum,
                'change_amount' => $change,
                'card_surcharge_amount' => $cardSurchargeAmount,
                'status' => 'completed',
                'completed_at' => now(),
                'cashier_id' => auth()->id(),
                'register_id' => $order->register_id ?? $register?->id,
                'payment_on_delivery' => false,
            ];

            if ($request->exists('waiter_id')) {
                $updates['waiter_id'] = $this->resolveWaiterId($data['waiter_id'] ?? null);
            }

            if (! empty($data['customer_id'])) {
                $updates['customer_id'] = $data['customer_id'];
            }

            $order->update($updates);

            $this->recordPaymentLines($order, $paymentLines, $register, $data['payment_notes'] ?? null);
            if ($register) {
                $register->increment('orders_count');
            }

            $this->finalizeFullyPaidOrder($order->fresh());

            DB::commit();

            $order->refresh()->loadMissing('waiterRating', 'customer', 'items.product');
            $needsRating = $order->waiter_id && !$order->waiterRating;
            $loyalty = null;
            $loyaltyService = app(LoyaltyService::class);
            if ($request->boolean('loyalty_redeem') && $order->customer) {
                try {
                    $loyalty = $loyaltyService->redeemFree(
                        $order->customer,
                        $order,
                        isset($data['loyalty_redeem_value']) ? (float) $data['loyalty_redeem_value'] : null
                    );
                } catch (\Throwable $e) {
                    Log::warning('Loyalty redeem failed on settle: '.$e->getMessage());
                }
            }
            $skipQty = $request->boolean('loyalty_redeem') ? 1 : 0;
            $earned = $loyaltyService->awardFromOrder($order, $skipQty);
            if ($earned) {
                $loyalty = array_merge($loyalty ?? [], $earned);
            }

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'change_amount' => (float) $order->change_amount,
                'print_url' => route('pos.print-receipt', $order),
                'needs_rating' => (bool) $needsRating,
                'waiter_id' => $order->waiter_id,
                'loyalty' => $loyalty,
                'message' => $needsRating
                    ? 'Paid — waiter panel will ask for guest rating'
                    : 'Bill settled',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS settle order failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bills waiter marked as "bring bill" — ready for cashier payment.
     */
    public function payBills()
    {
        if (! (bool) Setting::get('bring_bill_enabled', true)) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'orders' => [],
                'bring_bill_enabled' => false,
                'message' => 'Bring Bill is disabled in Settings',
            ]);
        }

        $orders = Order::with(['customer', 'table.floor', 'waiter:id,name', 'items'])
            ->where('payment_status', 'unpaid')
            ->where('is_void', false)
            ->whereNotNull('bill_requested_at')
            ->orderBy('bill_requested_at', 'asc')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'customer' => $order->customer?->name ?? 'Walk-in',
                'table_id' => $order->table_id,
                'table_name' => $order->table?->name
                    ?? match ($order->order_type) {
                        'takeaway' => 'Takeaway',
                        'express' => 'Express',
                        default => '-',
                    },
                'floor_name' => $order->table?->floor?->name ?? '',
                'waiter' => $order->waiter?->name,
                'total' => (float) $order->total_amount,
                'items_count' => $order->items->where('is_void', false)->count(),
                'bill_requested_at' => $order->bill_requested_at ? \App\Models\Setting::formatDateTime($order->bill_requested_at, 'H:i') : null,
                'elapsed' => $order->bill_requested_at?->diffForHumans() ?? $order->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'count' => $orders->count(),
            'orders' => $orders,
            'bring_bill_enabled' => true,
        ]);
    }

    public function payBillsCount()
    {
        if (! (bool) Setting::get('bring_bill_enabled', true)) {
            return response()->json(['success' => true, 'count' => 0, 'bring_bill_enabled' => false]);
        }

        $count = Order::query()
            ->where('payment_status', 'unpaid')
            ->where('is_void', false)
            ->whereNotNull('bill_requested_at')
            ->count();

        return response()->json(['success' => true, 'count' => $count, 'bring_bill_enabled' => true]);
    }

    /** Count of unpaid open bills (dine-in / takeaway / express / delivery). */
    public function openBillsCount()
    {
        $count = Order::query()
            ->whereIn('order_type', ['dine_in', 'takeaway', 'express', 'delivery'])
            ->openBill()
            ->count();

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Mark a delivery open bill as delivered → completed (leaves Open Bills).
     * Partner/COD unpaid dues stay on ledger if already recorded; bill is closed operationally.
     */
    public function markDeliveryDelivered(Order $order)
    {
        if ($order->order_type !== 'delivery') {
            return response()->json(['success' => false, 'message' => 'Not a delivery order'], 422);
        }
        if ($order->is_void || $order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Order is cancelled'], 422);
        }
        if ($order->completed_at || $order->status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Already completed',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'delivery_status' => 'delivered',
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            app(KitchenTicketService::class)->finalizeActiveTickets($order->fresh());

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark delivery delivered failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marked delivered — order completed',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    private function createKitchenOrders(Order $order, $items = null): array
    {
        return app(KitchenTicketService::class)->createKitchenOrders($order, $items);
    }

    private function deductStock(Order $order, $items = null): void
    {
        app(KitchenTicketService::class)->deductStock($order, $items);
    }

    /**
     * Full payment only: close leftover KDS tickets and release the table.
     * Pay-Now checkout must NOT call this — kitchen still needs those tickets.
     */
    protected function finalizeFullyPaidOrder(Order $order): void
    {
        $outstanding = round((float) $order->total_amount - (float) $order->paid_amount, 2);
        if ($order->payment_status !== 'paid' || $outstanding > 0.009) {
            return;
        }

        if (! in_array($order->status, ['completed', 'cancelled', 'refunded'], true)) {
            $order->update([
                'status' => 'completed',
                'completed_at' => $order->completed_at ?? now(),
            ]);
        }

        app(KitchenTicketService::class)->finalizeActiveTickets($order);
        RestaurantTable::syncOccupancy($order->table_id);
    }

    public function voidOrder(Request $request)
    {
        $user = auth()->user();
        if (! $user || (! $user->can('pos.void') && ! $user->can('orders.void'))) {
            abort(403, 'Not allowed to void orders');
        }

        $data = $request->validate([
            'order_id' => 'required|integer',
            'reason' => 'required|string|min:3|max:500',
            'action' => 'nullable|in:void,cancel',
        ]);

        $action = $data['action'] ?? 'void';
        $order = Order::findOrFail($data['order_id']);

        if ($order->is_void || $order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'This bill is already cancelled/voided'], 422);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot void/cancel a paid bill'], 422);
        }

        DB::beginTransaction();
        try {
            $liveItems = $order->items()->where('is_void', false)->get();

            $order->update([
                'is_void' => true,
                'void_type' => $action,
                'void_reason' => trim($data['reason']),
                'voided_by' => auth()->id(),
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
            ]);

            // Mark live lines void for audit trail
            $order->items()->where('is_void', false)->update([
                'is_void' => true,
                'void_reason' => $action === 'cancel' ? 'Bill cancelled' : 'Bill voided',
            ]);

            if ($liveItems->isNotEmpty()) {
                app(KitchenTicketService::class)->restoreStock($order, $liveItems);
            }

            app(KitchenTicketService::class)->cancelActiveTickets($order);
            RestaurantTable::syncOccupancy($order->table_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS void order failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $action === 'cancel' ? 'Bill cancelled' : 'Bill voided',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'action' => $action,
        ]);
    }

    /**
     * Mark an open-bill line (or whole unpaid order) as comp — price 0, not a discount.
     */
    public function markComp(Request $request)
    {
        $user = auth()->user();
        if (! $user || (! $user->can('pos.comp') && ! $user->can('orders.comp'))) {
            abort(403, 'Not allowed to comp');
        }

        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'order_item_id' => 'nullable|integer|exists:order_items,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $order = Order::findOrFail($data['order_id']);
        if ($order->is_void || $order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot comp a voided order'], 422);
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot comp a paid bill — use refund'], 422);
        }

        $reason = trim((string) ($data['reason'] ?? '')) ?: 'Comp';

        DB::beginTransaction();
        try {
            if (! empty($data['order_item_id'])) {
                $item = OrderItem::where('order_id', $order->id)->where('id', $data['order_item_id'])->firstOrFail();
                if ($item->is_void) {
                    return response()->json(['success' => false, 'message' => 'Item is voided'], 422);
                }
                $item->update([
                    'is_comp' => true,
                    'comp_reason' => $reason,
                    'discount_amount' => 0,
                    'total_price' => 0,
                ]);
                $item->addons()->update(['price' => 0]);
            } else {
                $order->items()->where('is_void', false)->each(function (OrderItem $item) use ($reason) {
                    $item->update([
                        'is_comp' => true,
                        'comp_reason' => $reason,
                        'discount_amount' => 0,
                        'total_price' => 0,
                    ]);
                    $item->addons()->update(['price' => 0]);
                });
            }

            $subtotal = (float) $order->items()->where('is_void', false)->sum('total_price');
            $afterDiscount = max(0, $subtotal - (float) $order->discount_amount);
            $taxRate = (bool) Setting::get('tax_enabled', false) ? (float) Setting::get('tax_rate', 0) : 0;
            $tax = $afterDiscount * ($taxRate / 100);
            $serviceRate = Setting::get('service_charge_enabled', false) ? (float) Setting::get('service_charge_rate', 0) : 0;
            $service = $afterDiscount * ($serviceRate / 100);
            $rounded = $this->applyPriceRounding($afterDiscount + $tax + $service);

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'service_charge' => $service,
                'rounding_amount' => $rounded['rounding_amount'],
                'total_amount' => $rounded['total'],
                'is_comp' => true,
                'comp_reason' => $reason,
                'comped_by' => auth()->id(),
                'comped_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS markComp failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Comp applied',
            'order_id' => $order->id,
            'total_amount' => (float) $order->total_amount,
            'is_comp' => true,
        ]);
    }

    /**
     * Refund a paid order (full or partial by amount). Method: cash or card.
     */
    public function refundOrder(Request $request)
    {
        $user = auth()->user();
        if (! $user || (! $user->can('pos.refund') && ! $user->can('orders.refund'))) {
            abort(403, 'Not allowed to refund');
        }

        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'amount' => 'nullable|numeric|min:0.01',
            'method' => 'required|in:cash,card',
            'reason' => 'nullable|string|max:500',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer',
            'full' => 'nullable|boolean',
        ]);

        $order = Order::with(['payments', 'items'])->findOrFail($data['order_id']);

        if ($order->is_void || $order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot refund a voided/cancelled order'], 422);
        }
        if ($order->payment_status !== 'paid' && $order->status !== 'refunded') {
            // Allow partial follow-up refunds on already partially refunded (status may still be completed)
            if ((float) $order->paid_amount <= 0) {
                return response()->json(['success' => false, 'message' => 'Order is not paid'], 422);
            }
        }

        $completedPayments = $order->payments->where('status', 'completed');
        $grossPaid = round((float) $completedPayments->filter(fn ($p) => (float) $p->amount > 0)->sum('amount'), 2);
        $alreadyRefunded = round(abs((float) $completedPayments->filter(fn ($p) => (float) $p->amount < 0)->sum('amount')), 2);
        $refundable = round(max(0, $grossPaid - $alreadyRefunded), 2);

        if ($refundable <= 0.009) {
            return response()->json(['success' => false, 'message' => 'Nothing left to refund'], 422);
        }

        $method = $data['method'];
        $hadCard = $completedPayments->contains(fn ($p) => $p->method === 'card' && (float) $p->amount > 0);
        if ($method === 'card' && ! $hadCard) {
            return response()->json(['success' => false, 'message' => 'Original order had no card payment'], 422);
        }

        $cardPaid = round((float) $completedPayments->filter(fn ($p) => $p->method === 'card' && (float) $p->amount > 0)->sum('amount'), 2);
        $cardRefunded = round(abs((float) $completedPayments->filter(fn ($p) => $p->method === 'card' && (float) $p->amount < 0)->sum('amount')), 2);
        $cardRefundable = round(max(0, $cardPaid - $cardRefunded), 2);
        $cashPaid = round((float) $completedPayments->filter(fn ($p) => $p->method === 'cash' && (float) $p->amount > 0)->sum('amount'), 2);
        $cashRefunded = round(abs((float) $completedPayments->filter(fn ($p) => $p->method === 'cash' && (float) $p->amount < 0)->sum('amount')), 2);
        $cashRefundable = round(max(0, $cashPaid - $cashRefunded), 2);

        $methodCap = $method === 'card' ? $cardRefundable : $cashRefundable;
        // Cash refunds can also refund non-cash remainder as cash if cashier chooses cash
        if ($method === 'cash') {
            $methodCap = $refundable;
        } else {
            $methodCap = min($refundable, $cardRefundable);
        }

        $isFull = $request->boolean('full') || empty($data['amount']);
        $refundAmount = $isFull ? $methodCap : round((float) $data['amount'], 2);
        if ($refundAmount > $methodCap + 0.009) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount exceeds refundable ('.$methodCap.')',
            ], 422);
        }

        // Proportional card surcharge is already inside card payment amounts; no extra add-on needed.
        $reason = trim((string) ($data['reason'] ?? '')) ?: 'refund';

        DB::beginTransaction();
        try {
            $register = CashRegister::getActiveForPos(auth()->id());
            if (! $register && $order->register_id) {
                $register = CashRegister::find($order->register_id);
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'amount' => -abs($refundAmount),
                'surcharge_amount' => null,
                'status' => 'completed',
                'notes' => $reason,
                'created_by' => auth()->id(),
            ]);

            if ($register) {
                if ($method === 'cash') {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('cash_registers', 'cash_refunds')) {
                        $register->increment('cash_refunds', $refundAmount);
                    } else {
                        $register->increment('cash_out', $refundAmount);
                    }
                } else {
                    $register->decrement('card_sales', min((float) $register->card_sales, $refundAmount));
                }
            }

            app(AccountService::class)->postRefund($payment->loadMissing('order'), $refundAmount);

            $newPaid = round(max(0, (float) $order->paid_amount - $refundAmount), 2);
            $fullyRefunded = $newPaid <= 0.009 || ($alreadyRefunded + $refundAmount) + 0.009 >= $grossPaid;

            $orderUpdates = [
                'paid_amount' => $newPaid,
            ];
            if ($fullyRefunded) {
                $orderUpdates['status'] = 'refunded';
                $orderUpdates['payment_status'] = 'paid';
            }
            $order->update($orderUpdates);

            $itemIds = $data['item_ids'] ?? [];
            if ($fullyRefunded && empty($itemIds)) {
                $restore = $order->items()->where('is_void', false)->get();
                if ($restore->isNotEmpty()) {
                    app(KitchenTicketService::class)->restoreStock($order, $restore);
                }
            } elseif (! empty($itemIds)) {
                $restore = $order->items()->where('is_void', false)->whereIn('id', $itemIds)->get();
                if ($restore->isNotEmpty()) {
                    app(KitchenTicketService::class)->restoreStock($order, $restore);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS refund failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund recorded',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'refund_amount' => $refundAmount,
            'method' => $method,
            'paid_amount' => (float) $order->fresh()->paid_amount,
            'status' => $order->fresh()->status,
        ]);
    }

    public function printReceipt(Request $request, Order $order)
    {
        $order->loadMissing(['cashier', 'waiter', 'table', 'customer', 'deliveryPartner', 'items.addons', 'items.options', 'payments.creator', 'branch']);
        $branch = $order->branch ?? \App\Services\BranchService::current();
        $settings = array_merge(\App\Services\BranchService::invoiceSettings($branch), [
            'tax_enabled' => (bool) Setting::get('tax_enabled', false),
            'tax_name' => Setting::get('tax_name', 'Tax'),
            'service_charge_enabled' => (bool) Setting::get('service_charge_enabled', false),
        ]);
        if (empty($settings['invoice_logo_src'])) {
            $settings['invoice_logo_src'] = Setting::invoiceLogoDataUri();
        }
        if ($request->query('format') === 'html') {
            return view('pos.receipt_80mm', compact('order', 'settings'));
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.receipt_80mm', compact('order', 'settings'))->setPaper([0, 0, 226.77, 2000]);
        return $pdf->stream('receipt-' . $order->order_number . '.pdf');
    }

    public function printKot(Request $request, Order $order)
    {
        $order->loadMissing(['waiter', 'table']);
        $query = KitchenOrder::with(['items.orderItem.addons', 'items.orderItem.options'])
            ->where('order_id', $order->id)
            ->where('type', 'kitchen');

        if ($request->filled('kitchen_order_id')) {
            $query->where('id', $request->kitchen_order_id);
        }

        $kot = $query->latest('id')->first();
        if (!$kot) {
            return response()->json(['success' => false, 'message' => 'No KOT found for this order'], 404);
        }

        $settings = \App\Services\BranchService::invoiceSettings($order->branch ?? \App\Services\BranchService::current());
        if ($request->query('format') === 'html') {
            return view('pos.kot_print', compact('order', 'kot', 'settings'));
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.kot_print', compact('order', 'kot', 'settings'))->setPaper([0, 0, 226.77, 2000]);
        return $pdf->stream('kot-' . $order->order_number . '.pdf');
    }

    public function printBot(Request $request, Order $order)
    {
        $order->loadMissing(['waiter', 'table']);
        $query = KitchenOrder::with(['items.orderItem.addons', 'items.orderItem.options'])
            ->where('order_id', $order->id)
            ->where('type', 'bar');

        if ($request->filled('kitchen_order_id')) {
            $query->where('id', $request->kitchen_order_id);
        }

        $bot = $query->latest('id')->first();
        if (!$bot) {
            return response()->json(['success' => false, 'message' => 'No BOT found for this order'], 404);
        }
        $settings = \App\Services\BranchService::invoiceSettings($order->branch ?? \App\Services\BranchService::current());
        if ($request->query('format') === 'html') {
            return view('pos.bot_print', compact('order', 'bot', 'settings'));
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.bot_print', compact('order', 'bot', 'settings'))->setPaper([0, 0, 226.77, 2000]);
        return $pdf->stream('bot-' . $order->order_number . '.pdf');
    }

    public function lastReceipt(Request $request)
    {
        $order = Order::whereNotNull('completed_at')->orWhere('paid_amount', '>=', DB::raw('total_amount'))->latest()->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'No completed order found'], 404);
        }
        return $this->printReceipt($request, $order);
    }

    public function lastKot(Request $request)
    {
        $kot = KitchenOrder::with(['order.waiter', 'order.table', 'items', 'kitchen'])->where('type', 'kitchen')->latest()->first();
        if (!$kot || !$kot->order) {
            return response()->json(['success' => false, 'message' => 'No KOT found'], 404);
        }
        if ($request->query('format') === 'json' || $request->wantsJson()) {
            $resolved = \App\Models\Kitchen::resolvePrinterForKitchenOrder($kot->kitchen, 'kitchen');

            return response()->json([
                'success' => true,
                'kitchen_order_id' => $kot->id,
                'order_id' => $kot->order_id,
                'kot_number' => $kot->kot_number,
                'type' => 'kot',
                'printer_ip' => $resolved['printer_ip'],
                'printer_port' => $resolved['printer_port'],
                'printer_name' => $resolved['printer_name'],
                'print_mode' => 'direct',
                'url' => route('pos.print-kot', ['order' => $kot->order_id, 'kitchen_order_id' => $kot->id]),
            ]);
        }
        $settings = Setting::getGroup('business');
        if ($request->query('format') === 'html') {
            return view('pos.kot_print', ['order' => $kot->order, 'kot' => $kot, 'settings' => $settings]);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.kot_print', ['order' => $kot->order, 'kot' => $kot, 'settings' => $settings])->setPaper([0, 0, 226.77, 2000]);
        return $pdf->stream('kot-' . $kot->order->order_number . '.pdf');
    }

    public function lastBot(Request $request)
    {
        $bot = KitchenOrder::with(['order.waiter', 'order.table', 'items'])->where('type', 'bar')->latest()->first();
        if (!$bot || !$bot->order) {
            return response()->json(['success' => false, 'message' => 'No BOT found'], 404);
        }
        $settings = Setting::getGroup('business');
        if ($request->query('format') === 'html') {
            return view('pos.bot_print', ['order' => $bot->order, 'bot' => $bot, 'settings' => $settings]);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.bot_print', ['order' => $bot->order, 'bot' => $bot, 'settings' => $settings])->setPaper([0, 0, 226.77, 2000]);
        return $pdf->stream('bot-' . $bot->order->order_number . '.pdf');
    }

    public function networkPrint(KitchenOrder $kitchenOrder, NetworkPrinterService $printer)
    {
        $result = $printer->printKitchenOrder($kitchenOrder);
        $status = ($result['success'] ?? false) ? 200 : 422;

        if ($result['success'] ?? false) {
            $this->markKitchenOrderPrinted($kitchenOrder);
        }

        return response()->json($result, $status);
    }

    /** ESC/POS bytes (base64) for Local Print Bridge — per kitchen printer. */
    public function kitchenOrderEscPos(KitchenOrder $kitchenOrder, NetworkPrinterService $printer)
    {
        $kitchenOrder->loadMissing('kitchen');
        $kitchen = $kitchenOrder->kitchen;
        $resolved = \App\Models\Kitchen::resolvePrinterForKitchenOrder($kitchen, (string) $kitchenOrder->type);
        $isBar = $kitchenOrder->type === 'bar';
        $hasPrinter = (bool) ($resolved['has_printer'] ?? false);

        if (! $hasPrinter) {
            return response()->json([
                'success' => false,
                'message' => $isBar
                    ? 'No bar/BOT printer configured — not sending to kitchen KOT printer'
                    : 'No kitchen printer configured',
                'kitchen_order_id' => $kitchenOrder->id,
                'order_id' => $kitchenOrder->order_id,
                'type' => $kitchenOrder->type,
                'has_printer' => false,
            ], 422);
        }

        $payload = $printer->escPosKitchenPayload($kitchenOrder);

        return response()->json([
            'success' => true,
            'kitchen_order_id' => $kitchenOrder->id,
            'order_id' => $kitchenOrder->order_id,
            'type' => $kitchenOrder->type,
            'ticket_kind' => $isBar ? 'bot' : 'kot',
            'printer_ip' => $resolved['printer_ip'],
            'printer_port' => $resolved['printer_port'],
            'printer_name' => $resolved['printer_name'],
            'print_mode' => 'direct',
            'has_printer' => true,
            'payload_base64' => base64_encode($payload),
        ]);
    }

    /**
     * KOTs/BOTs that still need Print Bridge (e.g. waiter PWA placed order on a phone).
     * POS PC polls this and prints via local bridge, then marks printed_at.
     *
     * Only FUTURE tickets: first call stores pos_pending_kot_since = now().
     * Anything created before that is ignored (no migrate / no reprint of old KOTs).
     */
    public function pendingKotPrints(Request $request)
    {
        $hours = max(1, min(48, (int) $request->get('hours', 12)));
        $branchId = \App\Services\BranchService::currentId();

        $sinceRaw = \App\Models\Setting::get('pos_pending_kot_since');
        if (! $sinceRaw) {
            $sinceRaw = now()->toDateTimeString();
            \App\Models\Setting::set(
                'pos_pending_kot_since',
                $sinceRaw,
                'pos',
                'Ignore KOTs created before pending-print feature was enabled',
                'string'
            );
        }
        try {
            $since = \Carbon\Carbon::parse((string) $sinceRaw);
        } catch (\Throwable $e) {
            $since = now();
        }

        // Never look further back than the feature enable time (future-only).
        $windowStart = now()->subHours($hours);
        $from = $since->greaterThan($windowStart) ? $since : $windowStart;

        $query = KitchenOrder::query()
            ->with(['order.table', 'kitchen', 'items'])
            ->whereNull('printed_at')
            ->whereIn('type', ['kitchen', 'bar'])
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $from)
            ->whereHas('order', function ($q) use ($branchId) {
                // Only open/unpaid bills (Place Order / waiter). Pay Now creates KOT
                // records for reprint but must not auto-print via this queue.
                $q->where('is_void', false)
                    ->where('payment_status', 'unpaid')
                    ->whereNotIn('status', ['cancelled', 'refunded']);
                \App\Services\BranchService::scopeOrders($q, $branchId);
            })
            ->latest('id');

        $tickets = $query->limit(50)->get();
        $jobs = collect();

        foreach ($tickets as $kot) {
            $kitchen = $kot->kitchen;
            $resolved = \App\Models\Kitchen::resolvePrinterForKitchenOrder($kitchen, (string) $kot->type);
            $hasPrinter = (bool) ($resolved['has_printer'] ?? false);

            // BOT with no bar printer: never send to KOT — mark skipped so queue stays clean
            if (! $hasPrinter) {
                if ($kot->type === 'bar') {
                    $this->markKitchenOrderPrinted($kot);
                }
                continue;
            }

            $order = $kot->order;
            $ip = trim((string) ($resolved['printer_ip'] ?? ''));

            $jobs->push([
                'kitchen_order_id' => $kot->id,
                'kot_number' => $kot->kot_number,
                'type' => $kot->type === 'bar' ? 'bot' : 'kot',
                'order_id' => $kot->order_id,
                'order_number' => $order?->order_number,
                'table_name' => $order?->table?->name,
                'kitchen_name' => $kitchen?->name ?? ($kot->type === 'bar' ? 'Bar' : 'Kitchen'),
                'items_count' => $kot->items->count(),
                'created_at' => optional($kot->created_at)->toDateTimeString(),
                'printer_ip' => $ip !== '' ? $ip : null,
                'printer_port' => $resolved['printer_port'],
                'printer_name' => $resolved['printer_name'],
                'print_mode' => $kot->type === 'kitchen' ? 'direct' : ($resolved['print_mode'] ?? 'preview'),
                'has_printer' => true,
                'url' => $kot->type === 'bar'
                    ? route('pos.print-bot', ['order' => $kot->order_id, 'kitchen_order_id' => $kot->id])
                    : route('pos.print-kot', ['order' => $kot->order_id, 'kitchen_order_id' => $kot->id]),
                'auto_print' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'count' => $jobs->count(),
            'jobs' => $jobs->values(),
            'since' => $since->toDateTimeString(),
        ]);
    }

    /**
     * Recent waiter-panel tickets for POS right-side alerts (table + waiter + KOT print status).
     */
    public function waiterOrderAlerts(Request $request)
    {
        $minutes = max(5, min(180, (int) $request->get('minutes', 90)));
        $branchId = \App\Services\BranchService::currentId();
        $from = now()->subMinutes($minutes);

        // Recent KOT/BOT on open bills from waiter PWA.
        // Include null branch_id (older waiter bills) so POS still alerts.
        $tickets = KitchenOrder::query()
            ->with(['order.table', 'order.waiter', 'kitchen', 'items'])
            ->whereIn('type', ['kitchen', 'bar'])
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $from)
            ->whereHas('order', function ($q) use ($branchId) {
                $q->where('is_void', false)
                    ->whereNotIn('status', ['cancelled', 'refunded'])
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->where(function ($q2) {
                        // Waiter PWA always sets waiter_id; also catch null-cashier dine-in
                        $q2->whereNotNull('waiter_id')
                            ->orWhere(function ($q3) {
                                $q3->whereNull('cashier_id')->where('order_type', 'dine_in');
                            });
                    });
                if ($branchId) {
                    $q->where(function ($q2) use ($branchId) {
                        $q2->where('branch_id', $branchId)->orWhereNull('branch_id');
                    });
                }
            })
            ->latest('id')
            ->limit(50)
            ->get();

        $alerts = $tickets->map(function (KitchenOrder $kot) {
            $order = $kot->order;
            if (! $order) {
                return null;
            }
            $resolved = \App\Models\Kitchen::resolvePrinterForKitchenOrder($kot->kitchen, (string) $kot->type);
            $hasPrinter = (bool) ($resolved['has_printer'] ?? false);
            $isBar = $kot->type === 'bar';

            return [
                'id' => $kot->id,
                'kitchen_order_id' => $kot->id,
                'kot_number' => $kot->kot_number,
                'type' => $isBar ? 'bot' : 'kot',
                'is_reorder' => (bool) $kot->is_reorder,
                'order_id' => $kot->order_id,
                'order_number' => $order->order_number,
                'table_name' => $order->table?->name ?? '—',
                'waiter_name' => $order->waiter?->name ?? 'Waiter',
                'items_count' => $kot->items->count(),
                'item_names' => $kot->items->take(4)->pluck('product_name')->values(),
                'printed' => ! empty($kot->printed_at),
                'printed_at' => optional($kot->printed_at)->toDateTimeString(),
                'has_printer' => $hasPrinter,
                'printer_ip' => ($resolved['printer_ip'] ?? '') !== '' ? $resolved['printer_ip'] : null,
                'printer_name' => $resolved['printer_name'] ?? null,
                'created_at' => optional($kot->created_at)->toDateTimeString(),
                'elapsed' => optional($kot->created_at)?->diffForHumans() ?? '',
            ];
        })->filter()->values();

        // Fallback: waiter bills updated recently with no ticket row yet (still notify POS)
        $ticketOrderIds = $alerts->pluck('order_id')->filter()->unique()->all();
        $orderAlerts = Order::query()
            ->with(['table', 'waiter', 'items' => fn ($q) => $q->where('is_void', false)])
            ->whereNotNull('waiter_id')
            ->where('is_void', false)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where(function ($q) use ($from) {
                $q->where('created_at', '>=', $from)
                    ->orWhere('updated_at', '>=', $from);
            })
            ->when($ticketOrderIds, fn ($q) => $q->whereNotIn('id', $ticketOrderIds))
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($q2) use ($branchId) {
                    $q2->where('branch_id', $branchId)->orWhereNull('branch_id');
                });
            })
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function (Order $order) {
                $items = $order->items->take(4);
                $sid = -1 * (int) $order->id;

                return [
                    'id' => $sid,
                    'kitchen_order_id' => $sid,
                    'kot_number' => null,
                    'type' => 'order',
                    'is_reorder' => false,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'table_name' => $order->table?->name ?? '—',
                    'waiter_name' => $order->waiter?->name ?? 'Waiter',
                    'items_count' => $order->items->count(),
                    'item_names' => $items->pluck('product_name')->values(),
                    'printed' => false,
                    'printed_at' => null,
                    'has_printer' => false,
                    'printer_ip' => null,
                    'printer_name' => null,
                    'created_at' => optional($order->updated_at ?? $order->created_at)->toDateTimeString(),
                    'elapsed' => optional($order->updated_at ?? $order->created_at)?->diffForHumans() ?? '',
                ];
            });

        $alerts = $alerts->concat($orderAlerts)->values();

        return response()->json([
            'success' => true,
            'count' => $alerts->count(),
            'alerts' => $alerts,
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    public function markKotPrinted(Request $request, KitchenOrder $kitchenOrder)
    {
        $this->markKitchenOrderPrinted($kitchenOrder);

        return response()->json([
            'success' => true,
            'kitchen_order_id' => $kitchenOrder->id,
            'printed_at' => optional($kitchenOrder->fresh()->printed_at)->toDateTimeString(),
        ]);
    }

    protected function markKitchenOrderPrinted(KitchenOrder $kitchenOrder): void
    {
        if ($kitchenOrder->printed_at) {
            return;
        }

        $kitchenOrder->update([
            'printed_at' => now(),
            'printed_by' => auth()->id(),
        ]);
    }

    public function networkPrintReceipt(Order $order, NetworkPrinterService $printer)
    {
        $result = $printer->printReceipt($order);
        $status = ($result['success'] ?? false) ? 200 : 422;

        return response()->json($result + ['order_id' => $order->id], $status);
    }

    public function networkPrintLastReceipt(NetworkPrinterService $printer)
    {
        $order = Order::query()
            ->where('payment_status', 'paid')
            ->where('is_void', false)
            ->latest('id')
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'No completed order found'], 404);
        }

        return $this->networkPrintReceipt($order, $printer);
    }

    /** ESC/POS bytes (base64) for Local Print Bridge on the POS PC. */
    public function receiptEscPos(Order $order, NetworkPrinterService $printer)
    {
        $payload = $printer->escPosReceiptPayload($order);
        $ip = trim((string) Setting::get('receipt_printer_ip', ''));
        $port = (int) Setting::get('receipt_printer_port', 9100);
        $name = trim((string) Setting::get('receipt_printer_name', 'XP-80C')) ?: 'XP-80C';

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'printer_ip' => $ip,
            'printer_port' => $port > 0 ? $port : 9100,
            'printer_name' => $name,
            'payload_base64' => base64_encode($payload),
        ]);
    }

    public function lastReceiptEscPos(NetworkPrinterService $printer)
    {
        $order = Order::query()
            ->where('payment_status', 'paid')
            ->where('is_void', false)
            ->latest('id')
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'No completed order found'], 404);
        }

        return $this->receiptEscPos($order, $printer);
    }

    public function kotDetails(KitchenOrder $kitchenOrder)
    {
        $kitchenOrder->load(['order', 'items.orderItem.product', 'items.orderItem.addons']);
        return response()->json([
            'kot' => [
                'id' => $kitchenOrder->id,
                'kot_number' => $kitchenOrder->kot_number,
                'type' => $kitchenOrder->type,
                'status' => $kitchenOrder->status,
                'order_id' => $kitchenOrder->order_id,
                'order_number' => $kitchenOrder->order?->order_number,
                'table_name' => $kitchenOrder->order?->table?->name,
                'items' => $kitchenOrder->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'order_item_id' => $item->order_item_id,
                        'product_id' => $item->orderItem?->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) ($item->orderItem?->unit_price ?? 0),
                        'addons' => $item->orderItem?->addons->map(function($addon) {
                            return [
                                'id' => $addon->id,
                                'addon_name' => $addon->addon_name,
                                'price' => (float) $addon->price,
                            ];
                        })->values()->all() ?? [],
                    ];
                }),
            ],
        ]);
    }

    public function updateKot(Request $request, KitchenOrder $kitchenOrder)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:kitchen_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = $kitchenOrder->order;
            $order->load('items.addons');
            $kotChanged = false;

            foreach ($data['items'] as $itemData) {
                $kotItem = $kitchenOrder->items()->where('id', $itemData['id'])->first();
                if (!$kotItem) {
                    continue;
                }

                $orderItem = $kotItem->orderItem;
                if (!$orderItem) {
                    continue;
                }

                $oldQty = (float) $kotItem->quantity;
                $newQty = (float) $itemData['quantity'];

                if ($newQty <= 0) {
                    // Remove item
                    $kotItem->delete();
                    $orderItem->delete();
                    $kotChanged = true;
                } elseif ($oldQty != $newQty) {
                    $kotItem->update(['quantity' => $newQty]);
                    $orderItem->update([
                        'quantity' => $newQty,
                        'total_price' => ($orderItem->unit_price + $orderItem->addons->sum('price')) * $newQty,
                    ]);
                    $kotChanged = true;
                }
            }

            // Recalculate order totals
            $order->refresh();
            $subtotal = $order->items->sum('total_price');
            $taxAmount = $subtotal * ($order->tax_rate / 100);
            $serviceCharge = $order->service_charge_rate ? ($subtotal * ($order->service_charge_rate / 100)) : 0;
            $discount = $order->discount_amount ?? 0;
            $total = $subtotal + $taxAmount + $serviceCharge - $discount;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'service_charge_amount' => $serviceCharge,
                'total_amount' => max(0, $total),
            ]);

            // If KOT has no items left, delete it
            if ($kitchenOrder->items()->count() === 0) {
                $kitchenOrder->delete();
            } elseif ($kotChanged) {
                $kitchenOrder->touch();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'KOT updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dineInOrders()
    {
        $orders = Order::with(['customer', 'table.floor', 'items.product', 'payments'])
            ->where('order_type', 'dine_in')
            ->openBill()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'customer' => $order->customer?->name ?? 'Walk-in',
                    'table_id' => $order->table_id,
                    'table_name' => $order->table?->name,
                    'floor_name' => $order->table?->floor?->name,
                    'table_capacity' => $order->table?->capacity,
                    'total' => (float) $order->total_amount,
                    'paid_amount' => (float) $order->paid_amount,
                    'balance' => (float) ($order->total_amount - $order->paid_amount),
                    'items_count' => $order->items->where('is_void', false)->count(),
                    'items' => $order->items->where('is_void', false)->values()->map(function($item) {
                        return [
                            'product_name' => $item->product_name,
                            'quantity' => (float) $item->quantity,
                            'total_price' => (float) $item->total_price,
                        ];
                    }),
                    'created_at' => \App\Models\Setting::formatDateTime($order->created_at, 'H:i:s'),
                    'elapsed' => $order->created_at->diffForHumans(),
                ];
            });

        $occupiedTableIds = Order::query()
            ->openBill()
            ->where('order_type', 'dine_in')
            ->whereNotNull('table_id')
            ->pluck('table_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $tables = \App\Models\RestaurantTable::with('floor')
            ->where('is_active', true)
            ->get()
            ->map(function($table) use ($occupiedTableIds) {
                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'capacity' => $table->capacity,
                    'floor_name' => $table->floor?->name ?? 'Main',
                    'status' => in_array((int) $table->id, $occupiedTableIds, true) ? 'occupied' : 'available',
                ];
            });

        return response()->json(['orders' => $orders, 'tables' => $tables]);
    }

    public function changeTable(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'table_id' => 'required|integer|exists:tables,id',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $oldTableId = $order->table_id;
        $newTableId = (int) $validated['table_id'];

        if ($oldTableId && (int) $oldTableId === $newTableId) {
            return response()->json(['success' => true, 'message' => 'Already on this table', 'order_number' => $order->order_number]);
        }

        $busy = Order::findOpenBillForTable($newTableId, $order->id);
        if ($busy) {
            return response()->json([
                'success' => false,
                'message' => 'Table already has open bill '.$busy->order_number.'. Settle or merge before moving.',
                'existing_order_id' => $busy->id,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Free old table only if no other open bill remains on it
            if ($oldTableId) {
                $stillOpen = Order::findOpenBillForTable((int) $oldTableId, $order->id);
                if (! $stillOpen) {
                    RestaurantTable::where('id', $oldTableId)->update(['status' => 'available']);
                }
            }

            $order->update(['table_id' => $newTableId]);
            RestaurantTable::where('id', $newTableId)->update(['status' => 'occupied']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Table changed successfully',
                'order_number' => $order->order_number,
                'new_table' => RestaurantTable::find($newTableId)?->name,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function assignWaiter(Request $request, Order $order)
    {
        if ($order->is_void) {
            return response()->json(['success' => false, 'message' => 'Cannot update a voided order'], 422);
        }

        $data = $request->validate([
            'waiter_id' => 'nullable|integer|exists:users,id',
        ]);

        $order->update([
            'waiter_id' => $this->resolveWaiterId($data['waiter_id'] ?? null),
        ]);

        $order->load('waiter');

        return response()->json([
            'success' => true,
            'waiter_id' => $order->waiter_id,
            'waiter_name' => $order->waiter?->name,
            'message' => $order->waiter_id ? 'Waiter assigned' : 'Waiter cleared',
        ]);
    }

    public function recentOrders()
    {
        $orders = Order::with(['customer', 'table', 'waiter', 'items.product', 'payments', 'kitchenOrders.items'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'is_void' => (bool) $order->is_void,
                    'is_comp' => (bool) $order->is_comp,
                    'customer' => $order->customer?->name ?? 'Walk-in',
                    'table' => $order->table?->name,
                    'waiter' => $order->waiter?->name,
                    'total' => (float) $order->total_amount,
                    'items_count' => $order->items->where('is_void', false)->count(),
                    'created_at' => \App\Models\Setting::formatDateTime($order->created_at, 'H:i:s'),
                    'has_kot' => $order->kitchenOrders->contains(fn ($k) => $k->type === 'kitchen'),
                    'has_bot' => $order->kitchenOrders->contains(fn ($k) => $k->type === 'bar'),
                    'kitchen_orders' => $order->kitchenOrders->map(function($kot) {
                        return [
                            'id' => $kot->id,
                            'kot_number' => $kot->kot_number,
                            'type' => $kot->type,
                            'status' => $kot->status,
                            'items_count' => $kot->items->count(),
                        ];
                    }),
                ];
            });

        return response()->json(['orders' => $orders]);
    }

    public function waiterReport(Request $request)
    {
        $range = $request->get('range', 'month');
        [$start, $end, $range] = WaiterReportService::resolveRange(
            $range,
            $request->get('from'),
            $request->get('to')
        );
        $report = WaiterReportService::build($start, $end);

        return response()->json([
            'success' => true,
            'range' => $range,
            'currency' => Setting::get('currency_symbol', 'LKR'),
            ...$report,
        ]);
    }

    protected function normalizePaymentLines(array $data, float $total): array
    {
        if (!empty($data['payments']) && is_array($data['payments'])) {
            return collect($data['payments'])
                ->map(fn ($p) => [
                    'method' => $p['method'],
                    'amount' => round((float) $p['amount'], 2),
                ])
                ->filter(fn ($p) => $p['amount'] > 0)
                ->values()
                ->all();
        }

        $method = $data['payment_method'] ?? 'cash';
        if ($method === 'split') {
            $method = 'cash';
        }

        $amount = $total;
        if (($method === 'cash') && isset($data['cash_received']) && (float) $data['cash_received'] > 0) {
            $amount = max($total, round((float) $data['cash_received'], 2));
        }

        return [['method' => $method, 'amount' => round($amount, 2)]];
    }

    /**
     * Round bill total up to the configured unit. Returns ['total' => float, 'rounding_amount' => float].
     */
    protected function applyPriceRounding(float $total): array
    {
        $enabled = (bool) Setting::get('price_rounding_enabled', true);
        $unit = (float) Setting::get('price_rounding_unit', 1);
        $mode = (string) Setting::get('price_rounding_mode', 'up');
        $total = round(max(0, $total), 2);

        if (! $enabled || $unit <= 0) {
            return ['total' => $total, 'rounding_amount' => 0.0];
        }

        if ($mode === 'up' || $mode === '') {
            $rounded = ceil(($total / $unit) - 1e-9) * $unit;
        } else {
            $rounded = round($total / $unit) * $unit;
        }
        $rounded = round($rounded, 2);
        $rounding = round(max(0, $rounded - $total), 2);

        return ['total' => $rounded, 'rounding_amount' => $rounding];
    }

    protected function cardSurchargePercent(): float
    {
        if (! (bool) Setting::get('card_surcharge_enabled', true)) {
            return 0.0;
        }

        return max(0.0, (float) Setting::get('card_surcharge_percent', 3));
    }

    /**
     * Add card surcharge onto card payment lines (base amounts in, charged amounts out).
     *
     * @param  array<int, array{method:string,amount:float}>  $paymentLines
     * @return array{lines: array<int, array{method:string,amount:float,surcharge_amount:float}>, surcharge: float}
     */
    protected function applyCardSurchargeToLines(array $paymentLines): array
    {
        $percent = $this->cardSurchargePercent();
        $totalSurcharge = 0.0;
        $out = [];

        foreach ($paymentLines as $p) {
            $base = round((float) $p['amount'], 2);
            $surcharge = 0.0;
            if ($percent > 0 && ($p['method'] ?? '') === 'card') {
                $surcharge = round($base * ($percent / 100), 2);
                $totalSurcharge = round($totalSurcharge + $surcharge, 2);
            }
            $out[] = [
                'method' => $p['method'],
                'amount' => round($base + $surcharge, 2),
                'surcharge_amount' => $surcharge,
            ];
        }

        return ['lines' => $out, 'surcharge' => $totalSurcharge];
    }

    protected function recordPaymentLines(Order $order, array $payments, ?CashRegister $register, ?string $notes = null): void
    {
        $accountService = app(AccountService::class);
        $changeLeft = (float) $order->change_amount;

        foreach ($payments as $p) {
            $method = $p['method'];
            $amount = (float) $p['amount'];
            $surcharge = (float) ($p['surcharge_amount'] ?? 0);

            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'amount' => $amount,
                'surcharge_amount' => $surcharge > 0 ? $surcharge : null,
                'reference_number' => $notes,
                'status' => 'completed',
                'notes' => $surcharge > 0 ? 'Includes card surcharge '.$surcharge : null,
                'created_by' => auth()->id(),
            ]);

            if ($register) {
                $salesField = match ($method) {
                    'cash' => 'cash_sales',
                    'card' => 'card_sales',
                    'bank_transfer' => 'bank_transfer_sales',
                    'online' => 'online_sales',
                    'credit' => 'credit_sales',
                    default => 'cash_sales',
                };
                $register->increment($salesField, $amount);
            }

            $ledgerAmount = $amount;
            if ($method === 'cash' && $changeLeft > 0) {
                $deduct = min($ledgerAmount, $changeLeft);
                $ledgerAmount = round($ledgerAmount - $deduct, 2);
                $changeLeft = round($changeLeft - $deduct, 2);
            }

            $accountService->postPayment($payment->loadMissing('order'), $ledgerAmount);
        }
    }

    protected function resolveWaiterId(?int $waiterId): ?int
    {
        if (auth()->user()?->isWaiter()) {
            return auth()->id();
        }

        if (!$waiterId) {
            return null;
        }

        $exists = User::query()
            ->where('id', $waiterId)
            ->where(function ($q) {
                $q->where('user_type', 'waiter')
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'waiter'));
            })
            ->exists();

        return $exists ? $waiterId : null;
    }

    /**
     * Resolve / create a Customer from takeaway cart name+phone and return customer_id.
     * Uses existing customers table so receipts/reprint already show name & phone.
     */
    protected function resolveTakeawayCustomerId(?string $name, ?string $phone, $existingId = null): ?int
    {
        $name = trim((string) $name);
        $phone = trim((string) $phone);

        if ($name === '' && $phone === '') {
            return $existingId ? (int) $existingId : null;
        }

        if ($phone !== '') {
            $digits = preg_replace('/\D+/', '', $phone) ?: $phone;
            $customer = Customer::query()
                ->whereRaw("REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', '') = ?", [$digits])
                ->first();

            if ($customer) {
                $updates = ['is_active' => true];
                if ($name !== '' && $customer->name !== $name) {
                    $updates['name'] = $name;
                }
                if ($customer->phone !== $phone) {
                    $updates['phone'] = $phone;
                }
                if (count($updates) > 1 || ! $customer->is_active) {
                    $customer->update($updates);
                }

                return (int) $customer->id;
            }

            $customer = Customer::create([
                'name' => $name !== '' ? $name : 'Takeaway',
                'phone' => $phone,
                'is_active' => true,
            ]);

            return (int) $customer->id;
        }

        // Name only — still attach so the bill can show the name
        $customer = Customer::create([
            'name' => $name,
            'phone' => null,
            'is_active' => true,
        ]);

        return (int) $customer->id;
    }

    /**
     * Apply takeaway name/phone onto $data['customer_id'] (and clear waiter).
     */
    protected function applyTakeawayCustomerFields(array &$data): void
    {
        if (($data['order_type'] ?? '') !== 'takeaway') {
            return;
        }

        $data['table_id'] = null;
        $data['waiter_id'] = null;

        $resolved = $this->resolveTakeawayCustomerId(
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null,
            $data['customer_id'] ?? null
        );

        $data['customer_id'] = $resolved;
    }

    /**
     * Validate that each cart item either has a valid product_id OR is_custom_item with a name.
     * Custom items must NOT have product_id set.
     */
    private function assertPosCartItems(array $items): void
    {
        foreach ($items as $idx => $item) {
            $isCustom = ! empty($item['is_custom_item']);
            if ($isCustom) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    throw ValidationException::withMessages([
                        "items.{$idx}.name" => 'Custom item name is required.',
                    ]);
                }
                $price = (float) ($item['price'] ?? 0);
                if ($price <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.price" => 'Custom item price must be greater than 0.',
                    ]);
                }
            } else {
                if (empty($item['product_id'])) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.product_id" => 'Each item must have a product_id or be marked as custom item.',
                    ]);
                }
            }
        }
    }

    /**
     * Create a single OrderItem (and its addons) from a POS cart payload entry.
     * Handles both normal products and custom items (is_custom_item=true).
     * $withLoyalty = true applies loyalty-free logic (checkout only).
     */
    private function createOrderItemFromPosPayload(Order $order, array $item, bool $withLoyalty): OrderItem
    {
        $isCustom = ! empty($item['is_custom_item']);
        $isComp = ! empty($item['is_comp']);
        $compReason = $isComp ? (trim((string) ($item['comp_reason'] ?? '')) ?: 'Comp') : null;
        $lineDiscount = $isComp ? 0 : $this->posLineDiscount($item);

        if ($isComp) {
            $user = auth()->user();
            if (! $user || (! $user->can('pos.comp') && ! $user->can('orders.comp'))) {
                abort(403, 'Not allowed to comp items');
            }
        }

        if ($isCustom) {
            $displayName = trim((string) ($item['name'] ?? 'Custom Item'));
            $unitPrice   = (float) $item['price'];
            $gross       = $unitPrice * (float) $item['quantity'];
            $orderItem   = OrderItem::create([
                'order_id'             => $order->id,
                'product_id'           => null,
                'product_variant_id'   => null,
                'is_custom_item'       => true,
                'product_name'         => $displayName,
                'quantity'             => $item['quantity'],
                'unit_price'           => $unitPrice,
                'discount_amount'      => $lineDiscount,
                'total_price'          => $isComp ? 0 : max(0, $gross - $lineDiscount),
                'special_instructions' => $item['special_instructions'] ?? null,
                'routed_to'            => 'kitchen', // custom items follow KOT by default
                'is_comp'              => $isComp,
                'comp_reason'          => $compReason,
            ]);
            return $orderItem;
        }

        // --- Normal product ---
        $product        = Product::with('category')->find($item['product_id']);
        $isLoyaltyFree  = $withLoyalty && ! empty($item['loyalty_free']);
        $displayName    = $item['name'] ?? ($product?->name ?? '');
        if (! empty($item['variant_name']) && ! str_contains($displayName, $item['variant_name'])) {
            $displayName .= ' ('.$item['variant_name'].')';
        }
        if ($isLoyaltyFree && ! str_contains(strtoupper($displayName), 'FREE')) {
            $displayName .= ' · '.LoyaltyService::rewardLabel();
        }
        if ($isComp && ! str_contains(strtoupper($displayName), 'COMP')) {
            $displayName .= ' · COMP';
        }
        $unitPrice   = ($isLoyaltyFree || $isComp) ? (float) $item['price'] : (float) $item['price'];
        if ($isLoyaltyFree) {
            $unitPrice = 0;
        }
        $addonSum    = ($isLoyaltyFree || $isComp) ? 0 : collect($item['addons'] ?? [])->sum('price');
        if ($isComp) {
            $addonSum = collect($item['addons'] ?? [])->sum('price'); // keep addons on unit display via create below at 0
        }
        $instructions = $item['special_instructions'] ?? null;
        if ($isLoyaltyFree) {
            $tag = 'LOYALTY FREE · '.LoyaltyService::rewardLabel();
            $instructions = trim(($instructions ? $instructions.' · ' : '').$tag);
        }
        if ($isComp) {
            $instructions = trim(($instructions ? $instructions.' · ' : '').'COMP'.($compReason ? ': '.$compReason : ''));
        }

        $gross = ($isLoyaltyFree ? 0 : ((float) $item['price'] + collect($item['addons'] ?? [])->sum('price'))) * (float) $item['quantity'];
        if ($isLoyaltyFree) {
            $gross = 0;
        }
        $discount = ($isLoyaltyFree || $isComp) ? 0 : $lineDiscount;

        $orderItem = OrderItem::create([
            'order_id'             => $order->id,
            'product_id'           => $item['product_id'],
            'product_variant_id'   => $item['variant_id'] ?? null,
            'is_custom_item'       => false,
            'product_name'         => $displayName,
            'quantity'             => $item['quantity'],
            'unit_price'           => $isLoyaltyFree ? 0 : (float) $item['price'],
            'discount_amount'      => $discount,
            'total_price'          => $isComp ? 0 : max(0, $gross - $discount),
            'special_instructions' => $instructions,
            'routed_to'            => match ($product?->category?->type ?? 'kot') {
                'bot'    => 'bar',
                'direct' => 'direct',
                default  => 'kitchen',
            },
            'is_comp'              => $isComp,
            'comp_reason'          => $compReason,
        ]);

        foreach ($item['addons'] ?? [] as $addon) {
            $addonId = isset($addon['id']) ? (int) $addon['id'] : null;
            $useShared = array_key_exists('shared', $addon)
                ? (bool) $addon['shared']
                : ($addonId > 0 && \App\Models\Addon::whereKey($addonId)->exists());

            OrderItemAddon::create([
                'order_item_id' => $orderItem->id,
                'product_addon_id' => $useShared ? null : $addonId,
                'addon_id' => $useShared ? $addonId : null,
                'addon_name' => $addon['name'] ?? $addon['addon_name'] ?? 'Addon',
                'price' => ($isLoyaltyFree || $isComp) ? 0 : (float) ($addon['price'] ?? 0),
                'hide_on_receipt' => ! empty($addon['hide_on_receipt']),
            ]);
        }

        foreach ($item['options'] ?? [] as $opt) {
            $optName = trim((string) ($opt['name'] ?? $opt['option_name'] ?? ''));
            if ($optName === '') {
                continue;
            }
            \App\Models\OrderItemOption::create([
                'order_item_id' => $orderItem->id,
                'option_set_id' => isset($opt['option_set_id']) ? (int) $opt['option_set_id'] : null,
                'option_id' => isset($opt['id']) ? (int) $opt['id'] : (isset($opt['option_id']) ? (int) $opt['option_id'] : null),
                'option_set_name' => trim((string) ($opt['option_set_name'] ?? $opt['set_name'] ?? 'Option')),
                'option_name' => $optName,
            ]);
        }

        return $orderItem;
    }

    /** Gross line total before item discount. */
    protected function posLineGross(array $item): float
    {
        $addonSum = collect($item['addons'] ?? [])->sum(fn ($a) => (float) ($a['price'] ?? 0));

        return ((float) ($item['price'] ?? 0) + $addonSum) * (float) ($item['quantity'] ?? 0);
    }

    /** Item-level discount amount (capped at line gross). */
    protected function posLineDiscount(array $item): float
    {
        if (! empty($item['is_comp'])) {
            return 0.0;
        }
        $raw = (float) ($item['discount'] ?? $item['discount_amount'] ?? 0);

        return min($this->posLineGross($item), max(0, $raw));
    }

    /** Net line total after item discount. */
    protected function posLineNet(array $item): float
    {
        if (! empty($item['is_comp'])) {
            return 0.0;
        }

        return max(0, $this->posLineGross($item) - $this->posLineDiscount($item));
    }

    public function orderDetails(Order $order)
    {
        $order->load(['customer', 'table.floor', 'waiter', 'cashier', 'items.product.category', 'items.variant', 'items.addons', 'kitchenOrders.items', 'kitchenOrders.kitchen', 'payments']);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'is_void' => (bool) $order->is_void,
                'is_comp' => (bool) $order->is_comp,
                'comp_reason' => $order->comp_reason,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer?->name,
                'customer_phone' => $order->customer?->phone,
                'table_id' => $order->table_id,
                'table_name' => $order->table?->name,
                'floor_name' => $order->table?->floor?->name,
                'waiter_id' => $order->waiter_id,
                'waiter_name' => $order->waiter?->name,
                'cashier_name' => $order->cashier?->name,
                'subtotal' => (float) $order->subtotal,
                'tax_amount' => (float) $order->tax_amount,
                'service_charge' => (float) $order->service_charge,
                'discount_amount' => (float) $order->discount_amount,
                'rounding_amount' => (float) ($order->rounding_amount ?? 0),
                'card_surcharge_amount' => (float) ($order->card_surcharge_amount ?? 0),
                'total_amount' => (float) $order->total_amount,
                'order_notes' => $order->order_notes,
                'items' => $order->items->where('is_void', false)->values()->map(function ($item) {
                    $variantName = $item->variant?->name;
                    if (! $variantName && $item->product_variant_id && preg_match('/\(([^)]+)\)\s*$/', (string) $item->product_name, $m)) {
                        $variantName = $m[1];
                    }

                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'is_custom_item' => (bool) $item->is_custom_item,
                        'product_name' => $item->product_name,
                        'name' => $item->product_name,
                        'quantity' => (float) $item->quantity,
                        'price' => (float) $item->unit_price,
                        'unit_price' => (float) $item->unit_price,
                        'discount_amount' => (float) ($item->discount_amount ?? 0),
                        'discount' => (float) ($item->discount_amount ?? 0),
                        'total_price' => (float) $item->total_price,
                        'is_comp' => (bool) $item->is_comp,
                        'comp_reason' => $item->comp_reason,
                        'routed_to' => $item->routed_to,
                        'special_instructions' => $item->special_instructions,
                        'variant_id' => $item->product_variant_id,
                        'variant_name' => $variantName,
                        'addons' => $item->addons->map(function ($addon) {
                            $shared = (bool) $addon->addon_id;

                            return [
                                'id' => $addon->addon_id ?? $addon->product_addon_id ?? $addon->id,
                                'name' => $addon->addon_name,
                                'addon_name' => $addon->addon_name,
                                'price' => (float) $addon->price,
                                'shared' => $shared,
                            ];
                        }),
                    ];
                }),
                'kitchen_orders' => $order->kitchenOrders->map(function ($kot) {
                    $kitchen = $kot->kitchen;
                    $resolved = \App\Models\Kitchen::resolvePrinterForKitchenOrder($kitchen, (string) $kot->type);
                    return [
                        'id' => $kot->id,
                        'kot_number' => $kot->kot_number,
                        'type' => $kot->type,
                        'status' => $kot->status,
                        'items_count' => $kot->items->count(),
                        'kitchen_name' => $kitchen?->name,
                        'printer_ip' => $resolved['printer_ip'] !== '' ? $resolved['printer_ip'] : null,
                        'printer_port' => $resolved['printer_port'],
                        'printer_name' => $resolved['printer_name'],
                        'print_mode' => $kot->type === 'kitchen' ? 'direct' : $resolved['print_mode'],
                    ];
                }),
            ],
        ]);
    }

    public function updateOrder(Request $request, Order $order)
    {
        if ($order->is_void) {
            return response()->json(['success' => false, 'message' => 'Cannot update a voided order'], 422);
        }

        $data = $request->validate([
            'items' => 'nullable|array',
            'items.*.order_item_id' => 'nullable|integer',
            'items.*.product_id' => 'nullable|integer',
            'items.*.is_custom_item' => 'nullable|boolean',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.price' => 'required_with:items|numeric',
            'items.*.name' => 'nullable|string',
            'items.*.addons' => 'nullable|array',
            'items.*.special_instructions' => 'nullable|string',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.variant_name' => 'nullable|string',
            'sync_items' => 'nullable|boolean',
            'discount_amount' => 'nullable|numeric',
            'discount_type' => 'nullable|in:fixed,percentage',
            'tax_rate' => 'nullable|numeric',
            'service_charge' => 'nullable|numeric',
            'order_notes' => 'nullable|string',
            'waiter_id' => 'nullable|integer|exists:users,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.is_comp' => 'nullable|boolean',
            'items.*.comp_reason' => 'nullable|string|max:255',
        ]);

        $items = $data['items'] ?? [];
        // Full-cart edit: the payload is the complete item list, so any live line missing
        // from it was removed by the cashier. Legacy callers post additions only.
        $syncItems = $request->boolean('sync_items')
            || collect($items)->contains(fn ($i) => ! empty($i['order_item_id']));

        if ($syncItems && count($items) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'An order must keep at least one item — use Void Order to cancel it',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $wasPaid = $order->payment_status === 'paid';
            $tickets = app(KitchenTicketService::class);

            // Takeaway / delivery / express must never retain a dine-in table
            if ($order->order_type !== 'dine_in' && $order->table_id) {
                $staleTableId = $order->table_id;
                $order->table_id = null;
                $order->save();
                RestaurantTable::syncOccupancy($staleTableId);
            }

            if ($order->order_type === 'takeaway' && ($request->exists('customer_name') || $request->exists('customer_phone'))) {
                $resolved = $this->resolveTakeawayCustomerId(
                    $data['customer_name'] ?? null,
                    $data['customer_phone'] ?? null,
                    $data['customer_id'] ?? $order->customer_id
                );
                $data['customer_id'] = $resolved;
                $order->customer_id = $resolved;
                $order->waiter_id = null;
                $order->save();
            } elseif ($request->exists('customer_id')) {
                $order->customer_id = $data['customer_id'] ?? null;
                $order->save();
            }

            if ($request->exists('waiter_id') || auth()->user()->isWaiter()) {
                if ($order->order_type === 'takeaway') {
                    $order->waiter_id = null;
                } else {
                    $order->waiter_id = $this->resolveWaiterId(
                        $request->exists('waiter_id') ? ($data['waiter_id'] ?? null) : $order->waiter_id
                    );
                }
                $order->save();
            }

            // Waiter-only update (no lines posted)
            if (count($items) === 0) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'new_total' => (float) $order->total_amount,
                    'print_jobs' => [],
                    'has_kot' => false,
                    'has_bot' => false,
                    'print_receipt_url' => route('pos.print-receipt', $order),
                    'was_paid' => false,
                    'payment_status' => $order->payment_status,
                    'waiter_id' => $order->waiter_id,
                    'waiter_name' => $order->fresh()->load('waiter')->waiter?->name,
                    'message' => 'Waiter updated',
                ]);
            }

            $ticketItems = collect();
            $qtyOverrides = [];

            if ($syncItems) {
                $sync = $this->reconcileOrderItems($order, $items, $tickets);
                $ticketItems = $sync['increase_items']->concat($sync['new_items']);
                $qtyOverrides = $sync['increase_overrides'];
                $itemsChanged = $ticketItems->isNotEmpty() || $sync['reduced_count'] > 0;
            } else {
                foreach ($items as $item) {
                    $ticketItems->push($this->createOrderItemFromPosPayload($order, $item, false));
                }
                $itemsChanged = $ticketItems->isNotEmpty();
            }

            $subtotal = (float) $order->items()->where('is_void', false)->sum('total_price');

            $discountInput = $data['discount_amount'] ?? $order->discount_amount;
            $discountType = $data['discount_type'] ?? 'fixed';
            $discount = $discountType === 'percentage'
                ? $subtotal * (($discountInput ?? 0) / 100)
                : ($discountInput ?? 0);

            $afterDiscount = max(0, $subtotal - $discount);
            $taxRate = (bool) Setting::get('tax_enabled', false)
                ? ($data['tax_rate'] ?? Setting::get('tax_rate', 0))
                : 0;
            $tax = $afterDiscount * ($taxRate / 100);
            $serviceChargeRate = $data['service_charge'] ?? (
                Setting::get('service_charge_enabled', false)
                    ? Setting::get('service_charge_rate', 0)
                    : 0
            );
            $serviceCharge = $afterDiscount * ($serviceChargeRate / 100);
            $total = $afterDiscount + $tax + $serviceCharge;
            $rounded = $this->applyPriceRounding($total);
            $total = $rounded['total'];

            // A changed total on a settled bill reopens it so the difference can be collected
            $reopened = $wasPaid && $itemsChanged;

            $updates = [
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'service_charge' => $serviceCharge,
                'rounding_amount' => $rounded['rounding_amount'],
                'total_amount' => $total,
                'is_comp' => $order->items()->where('is_void', false)->where('is_comp', true)->exists(),
                // Reopened bills must leave a closed status, or they drop out of Open Bills
                'status' => ($ticketItems->isNotEmpty() || $reopened) ? 'pending' : $order->status,
                'order_notes' => array_key_exists('order_notes', $data) ? $data['order_notes'] : $order->order_notes,
            ];

            // Never blanked: an unselected picker must not detach the order's customer
            if (! empty($data['customer_id'])) {
                $updates['customer_id'] = $data['customer_id'];
            }

            if ($reopened) {
                $updates['payment_status'] = 'unpaid';
                $updates['paid_amount'] = 0;
                $updates['change_amount'] = 0;
                $updates['completed_at'] = null;
            }

            $order->update($updates);
            if ($reopened) {
                RestaurantTable::syncOccupancy($order->table_id);
            }

            // Tickets / stock only for added lines and quantity increases (retail = no KOT).
            // Tickets raised against an already-placed order print as REORDER.
            $printJobs = [];
            if ($ticketItems->isNotEmpty()) {
                $freshItems = $order->items()->with(['product.category.kitchen'])->whereIn('id', $ticketItems->pluck('id'))->get();
                $printJobs = $tickets->createKitchenOrders($order, $freshItems, $qtyOverrides, true);
                $tickets->deductStock($order, $freshItems->map(fn ($item) => array_key_exists($item->id, $qtyOverrides)
                    ? $this->stockProxy($item, (float) $qtyOverrides[$item->id])
                    : $item));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_total' => (float) $total,
                'print_jobs' => $printJobs,
                'has_kot' => collect($printJobs)->contains(fn ($j) => ($j['type'] ?? '') === 'kot'),
                'has_bot' => collect($printJobs)->contains(fn ($j) => ($j['type'] ?? '') === 'bot'),
                'print_receipt_url' => route('pos.print-receipt', $order),
                'was_paid' => $reopened,
                'payment_status' => $order->fresh()->payment_status,
                'items_changed' => $itemsChanged,
                'is_reorder' => $ticketItems->isNotEmpty(),
                'message' => $itemsChanged ? 'Bill updated' : 'No item changes to save',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS update order failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reconcile an order's live lines against the full POS cart payload.
     * Lines absent from the payload are voided (kept for audit), quantity changes
     * adjust the line in place, and only additions / increases reach the kitchen.
     */
    protected function reconcileOrderItems(Order $order, array $items, KitchenTicketService $tickets): array
    {
        $keepLines = collect($items)
            ->filter(fn ($line) => ! empty($line['order_item_id']))
            ->keyBy(fn ($line) => (int) $line['order_item_id']);
        $newLines = collect($items)->filter(fn ($line) => empty($line['order_item_id']));

        $increaseItems = collect();
        $increaseOverrides = [];
        $restoreItems = collect();
        $reducedCount = 0;

        $currentItems = $order->items()
            ->with(['addons', 'product.category.kitchen'])
            ->where('is_void', false)
            ->get();

        foreach ($currentItems as $item) {
            $keep = $keepLines->get($item->id);

            if (! $keep) {
                $restoreItems->push($this->stockProxy($item, (float) $item->quantity));
                $tickets->reduceKitchenQuantity($item, (float) $item->quantity);
                $item->update([
                    'is_void' => true,
                    'void_reason' => 'Removed while editing order',
                    'total_price' => 0,
                ]);
                $reducedCount++;
                continue;
            }

            $oldQty = (float) $item->quantity;
            $newQty = (float) $keep['quantity'];
            $newNote = array_key_exists('special_instructions', $keep)
                ? ($keep['special_instructions'] ?: null)
                : $item->special_instructions;
            $addonSum = (float) $item->addons->sum('price');
            $isComp = array_key_exists('is_comp', $keep) ? ! empty($keep['is_comp']) : (bool) $item->is_comp;
            $compReason = $isComp
                ? (trim((string) ($keep['comp_reason'] ?? $item->comp_reason ?? '')) ?: 'Comp')
                : null;
            if ($isComp && ! $item->is_comp) {
                $user = auth()->user();
                if (! $user || (! $user->can('pos.comp') && ! $user->can('orders.comp'))) {
                    abort(403, 'Not allowed to comp items');
                }
            }
            $lineDiscount = $isComp ? 0 : $this->posLineDiscount(array_merge($keep, [
                'price' => (float) $item->unit_price,
                'addons' => $item->addons->map(fn ($a) => ['price' => (float) $a->price])->all(),
                'quantity' => $newQty,
            ]));
            $newTotal = $isComp ? 0 : max(0, (((float) $item->unit_price + $addonSum) * $newQty) - $lineDiscount);

            if (
                abs($newQty - $oldQty) > 0.0001
                || $newNote !== $item->special_instructions
                || abs($lineDiscount - (float) $item->discount_amount) > 0.009
                || abs($newTotal - (float) $item->total_price) > 0.009
                || (bool) $item->is_comp !== $isComp
            ) {
                $item->update([
                    'quantity' => $newQty,
                    'discount_amount' => $lineDiscount,
                    'total_price' => $newTotal,
                    'special_instructions' => $newNote,
                    'is_comp' => $isComp,
                    'comp_reason' => $compReason,
                ]);
            }

            if ($newQty > $oldQty + 0.0001) {
                $increaseOverrides[$item->id] = $newQty - $oldQty;
                $increaseItems->push($item);
            } elseif ($oldQty > $newQty + 0.0001) {
                $delta = $oldQty - $newQty;
                $restoreItems->push($this->stockProxy($item, $delta));
                $tickets->reduceKitchenQuantity($item, $delta);
                $reducedCount++;
            }
        }

        $newItems = collect();
        foreach ($newLines as $line) {
            $newItems->push($this->createOrderItemFromPosPayload($order, $line, false));
        }

        if ($restoreItems->isNotEmpty()) {
            $tickets->restoreStock($order, $restoreItems);
        }

        return [
            'new_items' => $newItems,
            'increase_items' => $increaseItems,
            'increase_overrides' => $increaseOverrides,
            'reduced_count' => $reducedCount,
        ];
    }

    /** Unsaved clone carrying an adjusted quantity, for partial stock deduct / restore. */
    protected function stockProxy(OrderItem $item, float $quantity): OrderItem
    {
        $proxy = $item->replicate();
        $proxy->id = $item->id;
        $proxy->quantity = $quantity;
        $proxy->setRelation('product', $item->product);

        return $proxy;
    }

    public function broadcastCart(Request $request)
    {
        $active = $request->boolean('active');
        $items = $request->input('items', []);
        $seq = (int) $request->input('seq', 0);
        $lastSeq = (int) cache()->get('customer_display_cart_seq', 0);

        // Ignore stale POS broadcasts (e.g. in-flight cart push after payment cleared the display)
        if ($seq > 0 && $seq < $lastSeq) {
            return response()->json([
                'success' => true,
                'ignored' => true,
                'cleared' => ! cache()->has('customer_display_cart'),
            ]);
        }

        if ($seq > $lastSeq) {
            cache()->put('customer_display_cart_seq', $seq, now()->addMinutes(30));
        }

        // Placed / cleared cart → always wipe customer display (no strict validation)
        if (! $active || ! is_array($items) || count($items) === 0) {
            cache()->forget('customer_display_cart');
            cache()->put('customer_display_cleared_at', now()->timestamp, now()->addMinutes(30));

            return response()->json(['success' => true, 'cleared' => true]);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|numeric',
            'items.*.unitPrice' => 'required|numeric',
            'items.*.lineTotal' => 'required|numeric',
            'totals.subtotal' => 'required|numeric',
            'totals.tax' => 'nullable|numeric',
            'totals.discount' => 'nullable|numeric',
            'totals.total' => 'required|numeric',
            'meta' => 'nullable|array',
            'meta.customer' => 'nullable|string',
            'meta.order_type' => 'nullable|string',
            'meta.table' => 'nullable|string',
            'meta.invoice' => 'nullable|string',
            'payment' => 'nullable|array',
            'payment.paying' => 'nullable|numeric',
            'payment.change' => 'nullable|numeric',
            'payment.balance' => 'nullable|numeric',
            'active' => 'required|boolean',
            'seq' => 'nullable|integer',
        ]);

        cache()->forget('customer_display_cleared_at');
        cache()->put('customer_display_cart', $validated, now()->addMinutes(30));

        return response()->json(['success' => true, 'cleared' => false]);
    }
}
