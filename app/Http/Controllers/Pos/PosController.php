<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Floor;
use App\Models\HeldOrder;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::many([
            'tax_enabled' => false,
            'tax_rate' => 0,
            'tax_name' => 'VAT',
            'service_charge_rate' => 0,
            'service_charge_enabled' => false,
            'currency_symbol' => 'LKR',
            'table_selection_required' => false,
            'show_screen_numbers_keyboard' => true,
            'print_ask_before' => true,
            'auto_print_kot' => false,
            'auto_print_receipt' => false,
            'pos_bridge_print_pending_kot' => true,
            'receipt_print_mode' => 'preview',
            'receipt_print_bridge_url' => 'http://127.0.0.1:18181',
            'receipt_printer_ip' => '',
            'receipt_printer_port' => 9100,
            'receipt_printer_name' => 'XP-80C',
            'open_drawer_after_print' => true,
            'shortcut_focus_search' => 'F2',
            'shortcut_place_order' => 'F6',
            'shortcut_pay_now' => 'F7',
            'shortcut_open_bills' => 'F8',
            'pos_ui_mode' => 'restaurant',
            'bakery_show_dine_in' => true,
            'bakery_show_delivery' => false,
            'bakery_show_express' => false,
            'bakery_disable_kot' => false,
            'bakery_direct_billing' => false,
            'bakery_category_ids' => '[]',
            'bakery_show_cart_display' => true,
            'bakery_show_status_display' => false,
            'bakery_show_orders_display' => false,
            'customer_display_mode' => 'digital',
            'customer_display_protocol' => 'plain',
            'customer_display_baud' => 9600,
            'price_rounding_enabled' => true,
            'price_rounding_mode' => 'up',
            'price_rounding_unit' => 1,
            'card_surcharge_enabled' => true,
            'card_surcharge_percent' => 3,
        ]);

        $settings['tax_enabled'] = (bool) $settings['tax_enabled'];
        $settings['tax_rate'] = (float) $settings['tax_rate'];
        $settings['service_charge_rate'] = (float) $settings['service_charge_rate'];
        $settings['service_charge_enabled'] = (bool) $settings['service_charge_enabled'];
        $settings['price_rounding_enabled'] = (bool) $settings['price_rounding_enabled'];
        $settings['price_rounding_mode'] = (string) ($settings['price_rounding_mode'] ?: 'up');
        $settings['price_rounding_unit'] = (float) ($settings['price_rounding_unit'] ?: 1);
        $settings['card_surcharge_enabled'] = (bool) $settings['card_surcharge_enabled'];
        $settings['card_surcharge_percent'] = (float) ($settings['card_surcharge_percent'] ?? 3);
        $settings['table_selection_required'] = (bool) $settings['table_selection_required'];
        $settings['show_screen_numbers_keyboard'] = (bool) $settings['show_screen_numbers_keyboard'];
        $settings['print_ask_before'] = (bool) $settings['print_ask_before'];
        $settings['auto_print_kot'] = (bool) $settings['auto_print_kot'];
        $settings['auto_print_receipt'] = (bool) $settings['auto_print_receipt'];
        $settings['pos_bridge_print_pending_kot'] = (bool) ($settings['pos_bridge_print_pending_kot'] ?? true);
        $settings['receipt_print_mode'] = in_array($settings['receipt_print_mode'] ?? '', ['direct', 'preview'], true)
            ? $settings['receipt_print_mode']
            : 'preview';
        $settings['receipt_print_bridge_url'] = (string) ($settings['receipt_print_bridge_url'] ?? 'http://127.0.0.1:18181');
        $settings['receipt_printer_ip'] = (string) ($settings['receipt_printer_ip'] ?? '');
        $settings['receipt_printer_port'] = (int) ($settings['receipt_printer_port'] ?: 9100);
        $settings['receipt_printer_name'] = (string) ($settings['receipt_printer_name'] ?: 'XP-80C');
        $settings['open_drawer_after_print'] = (bool) ($settings['open_drawer_after_print'] ?? true);
        $settings['shortcut_focus_search'] = (string) $settings['shortcut_focus_search'];
        $settings['shortcut_place_order'] = (string) $settings['shortcut_place_order'];
        $settings['shortcut_pay_now'] = (string) $settings['shortcut_pay_now'];
        $settings['shortcut_open_bills'] = (string) $settings['shortcut_open_bills'];
        $settings['pos_ui_mode'] = in_array($settings['pos_ui_mode'] ?? '', ['restaurant', 'bakery', 'ice_cream'], true)
            ? $settings['pos_ui_mode']
            : 'restaurant';

        // Session override from Shop UI picker (only when picker is enabled)
        if (\App\Http\Controllers\Auth\ShopUiController::isPickerEnabled()) {
            $sessionMode = session(\App\Http\Controllers\Auth\ShopUiController::SESSION_KEY);
            if (in_array($sessionMode, ['restaurant', 'bakery', 'ice_cream'], true)) {
                $settings['pos_ui_mode'] = $sessionMode;
            }
        }
        $settings['bakery_show_dine_in'] = (bool) $settings['bakery_show_dine_in'];
        $settings['bakery_show_delivery'] = (bool) $settings['bakery_show_delivery'];
        $settings['bakery_show_express'] = (bool) $settings['bakery_show_express'];
        $settings['bakery_disable_kot'] = (bool) $settings['bakery_disable_kot'];
        $settings['bakery_direct_billing'] = (bool) $settings['bakery_direct_billing'];
        $settings['bakery_show_cart_display'] = (bool) $settings['bakery_show_cart_display'];
        $settings['bakery_show_status_display'] = (bool) $settings['bakery_show_status_display'];
        $settings['bakery_show_orders_display'] = (bool) $settings['bakery_show_orders_display'];
        $settings['customer_display_mode'] = in_array($settings['customer_display_mode'] ?? '', ['digital', 'analog'], true)
            ? $settings['customer_display_mode']
            : 'digital';
        $settings['customer_display_protocol'] = in_array($settings['customer_display_protocol'] ?? '', ['plain', 'escpos', 'dsp800'], true)
            ? $settings['customer_display_protocol']
            : 'plain';
        $settings['customer_display_baud'] = (int) ($settings['customer_display_baud'] ?: 9600);

        $bakeryCategoryIds = json_decode((string) ($settings['bakery_category_ids'] ?? '[]'), true);
        if (! is_array($bakeryCategoryIds)) {
            $bakeryCategoryIds = [];
        }
        $bakeryCategoryIds = array_values(array_unique(array_filter(array_map('intval', $bakeryCategoryIds))));
        $settings['bakery_category_ids'] = $bakeryCategoryIds;

        $isShopUi = in_array($settings['pos_ui_mode'], ['bakery', 'ice_cream'], true);

        if ($isShopUi && $settings['bakery_direct_billing']) {
            $settings['bakery_disable_kot'] = true;
            $settings['bakery_show_dine_in'] = false;
        }

        if (! $settings['tax_enabled']) {
            $settings['tax_rate'] = 0;
        }

        $categoriesQuery = Category::active()
            ->posVisible()
            ->orderBy('display_order')
            ->orderBy('name');

        if ($isShopUi && $bakeryCategoryIds !== []) {
            $categoriesQuery->whereIn('id', $bakeryCategoryIds);
        }

        $branchId = \App\Services\BranchService::enabled() ? \App\Services\BranchService::currentId() : null;

        $categories = $categoriesQuery
            ->with(['products' => function ($q) use ($branchId) {
                $q->available()
                    ->posVisible()
                    ->forBranch($branchId)
                    ->orderBy('display_order')
                    ->orderBy('name')
                    ->select([
                        'id', 'category_id', 'subcategory_id', 'name', 'code', 'barcode', 'image',
                        'selling_price', 'discount_amount', 'discount_type',
                        'has_variants', 'has_addons', 'display_order',
                        'is_available', 'show_in_pos',
                    ])
                    ->with([
                        'variants' => fn ($vq) => $vq->active()->select([
                            'id', 'product_id', 'name', 'price_adjustment', 'is_active',
                        ]),
                        'addons' => fn ($aq) => $aq->active()->select([
                            'id', 'product_id', 'name', 'price', 'is_active',
                        ]),
                        'partnerPrices:id,product_id,delivery_partner_id,price',
                    ]);
            }])
            ->with(['subcategories' => fn ($q) => $q->active()->orderBy('display_order')->orderBy('name')])
            ->get(['id', 'name', 'display_order', 'is_active', 'show_in_pos', 'type', 'color', 'image']);

        $floorsQuery = Floor::active()->with(['tables' => function ($q) {
            $q->active();
        }]);
        if ($branchId) {
            $floorsQuery->where('branch_id', $branchId);
        }
        $floors = $floorsQuery->get();

        $tablesQuery = RestaurantTable::with('floor:id,name,branch_id')->active();
        if ($branchId) {
            $tablesQuery->whereHas('floor', fn ($q) => $q->where('branch_id', $branchId));
        }
        $tables = $tablesQuery->get();

        $openOrderByTable = Order::query()
            ->openBill()
            ->where('order_type', 'dine_in')
            ->whereNotNull('table_id')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('id')
            ->get(['id', 'table_id', 'order_number'])
            ->unique('table_id')
            ->keyBy('table_id');

        $floors->each(function ($floor) use ($openOrderByTable) {
            $floor->tables->each(function ($table) use ($openOrderByTable) {
                $open = $openOrderByTable->get($table->id);
                $table->setAttribute('open_order_id', $open?->id);
                $table->setAttribute('open_order_number', $open?->order_number);
            });
        });

        $customers = Customer::active()
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'address', 'is_active', 'loyalty_joined', 'loyalty_stamps', 'loyalty_free_drinks']);

        $heldOrders = HeldOrder::with(['table:id,name', 'customer:id,name,phone'])
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(30)
            ->get();

        $waiters = User::active()
            ->where(function ($q) {
                $q->where('user_type', 'waiter')
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'waiter'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $lastSale = Order::query()
            ->where('payment_status', 'paid')
            ->where('is_void', false)
            ->latest('id')
            ->first(['order_number', 'total_amount', 'paid_amount', 'change_amount']);

        $subcategoryMap = $categories->mapWithKeys(function ($c) {
            return [
                (string) $c->id => $c->subcategories->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                    ];
                })->values()->all(),
            ];
        })->all();

        return view('pos.index', compact(
            'categories',
            'tables',
            'floors',
            'customers',
            'heldOrders',
            'waiters',
            'settings',
            'lastSale',
            'subcategoryMap'
        ));
    }

    public function dineIn()
    {
        return view('pos.dine-in');
    }
}
