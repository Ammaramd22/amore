<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Cheque;
use App\Models\BilliardBooking;
use App\Models\Setting;
use App\Services\BilliardsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && $user->isWaiter()) {
            return redirect()->route('waiter.dashboard');
        }

        if ($user && $user->isKitchenStaff()) {
            return redirect()->route('kds.index');
        }

        if ($user && $user->isBilliardsStaff()) {
            return redirect()->route('billiards.desk');
        }

        $range = $request->get('range', 'today');
        $from = $request->get('from');
        $to = $request->get('to');
        $branchFilter = \App\Services\BranchService::resolveFilter($request->get('branch_id'));
        $branchId = $branchFilter['id'];

        [$startDate, $endDate] = $this->getDateRange($range, $from, $to);

        $baseOrders = \App\Services\BranchService::scopeOrders(
            Order::whereBetween('created_at', [$startDate, $endDate])->notVoid(),
            $branchId
        );

        $agg = (clone $baseOrders)->selectRaw("
            COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as sales_total,
            COUNT(*) as orders_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN order_type = 'dine_in' THEN 1 ELSE 0 END) as dine_in_count,
            SUM(CASE WHEN order_type = 'takeaway' THEN 1 ELSE 0 END) as takeaway_count,
            SUM(CASE WHEN order_type = 'delivery' THEN 1 ELSE 0 END) as delivery_count
        ")->first();

        $sales = (float) ($agg->sales_total ?? 0);
        $ordersCount = (int) ($agg->orders_count ?? 0);
        $completedCount = (int) ($agg->completed_count ?? 0);

        $data = [
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'branchFilter' => $branchFilter,
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'today_sales' => $sales,
            'today_orders' => $ordersCount,
            'avg_order' => $completedCount > 0 ? $sales / $completedCount : 0,
            'dine_in_orders' => (int) ($agg->dine_in_count ?? 0),
            'takeaway_orders' => (int) ($agg->takeaway_count ?? 0),
            'delivery_orders' => (int) ($agg->delivery_count ?? 0),
            'pending_kitchen' => (int) \App\Services\BranchService::scopeOrders(
                Order::whereIn('status', ['pending', 'preparing'])->notVoid(),
                $branchId
            )->count(),
            'completed_orders' => $completedCount,
            'low_stock_items' => Ingredient::whereColumn('stock_quantity', '<=', 'reorder_level')
                ->active()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name', 'stock_quantity', 'reorder_level', 'unit', 'branch_id']),
            'expiry_remind_days' => max(0, (int) Setting::get('expiry_remind_days', 7)),
            'expiring_items' => collect(),
            'best_selling' => DB::table('order_items')
                ->select('products.name', DB::raw('SUM(order_items.quantity) as total_qty'))
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.is_void', false)
                ->whereBetween('order_items.created_at', [$startDate, $endDate])
                ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_qty')
                ->limit(10)
                ->get(),
            'sales_chart' => $this->getSalesChart($startDate, $endDate, $branchId),
            'recent_orders' => \App\Services\BranchService::scopeOrders(
                Order::with(['customer', 'table', 'waiter:id,name'])->notVoid()->latest(),
                $branchId
            )->limit(8)->get(),
            'active_customers' => Customer::active()->count(),
            'range_label' => match ($range) {
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'this_week' => 'This week',
                'this_month' => 'This month',
                'custom' => 'Custom range',
                default => ucfirst(str_replace('_', ' ', $range)),
            },
            'cheque_management_enabled' => Cheque::managementEnabled(),
            'cheque_pending_count' => 0,
            'cheque_pending_total' => 0,
            'cheque_overdue_count' => 0,
            'cheque_dues' => collect(),
            'billiards_enabled' => BilliardsService::enabled(),
            'billiards_today_count' => 0,
            'billiards_active_count' => 0,
            'billiards_unpaid_count' => 0,
            'billiards_ending_soon' => collect(),
            'billiards_today' => collect(),
        ];

        $remindDays = $data['expiry_remind_days'];
        $until = Carbon::today()->addDays($remindDays)->endOfDay();
        $expiringProducts = Product::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $until)
            ->orderBy('expiry_date')
            ->limit(40)
            ->get(['id', 'name', 'image', 'stock_quantity', 'expiry_date'])
            ->map(fn ($p) => (object) [
                'id' => $p->id,
                'name' => $p->name,
                'type' => 'product',
                'image' => $p->imageUrl(),
                'stock' => $p->stock_quantity,
                'unit' => 'qty',
                'expiry_date' => $p->expiry_date,
                'url' => route('products.edit', $p),
            ]);
        $expiringIngredients = Ingredient::query()
            ->active()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $until)
            ->orderBy('expiry_date')
            ->limit(40)
            ->get(['id', 'name', 'stock_quantity', 'unit', 'expiry_date'])
            ->map(fn ($i) => (object) [
                'id' => $i->id,
                'name' => $i->name,
                'type' => 'ingredient',
                'image' => '/images/product-placeholder.svg',
                'stock' => $i->stock_quantity,
                'unit' => $i->unit ?: 'qty',
                'expiry_date' => $i->expiry_date,
                'url' => route('ingredients.edit', $i),
            ]);
        $data['expiring_items'] = $expiringProducts
            ->concat($expiringIngredients)
            ->sortBy(fn ($row) => $row->expiry_date?->format('Y-m-d') ?? '9999-99-99')
            ->values();

        if ($data['cheque_management_enabled']) {
            $pending = Cheque::with('supplier')->pending()->orderBy('due_date')->get();
            $data['cheque_pending_count'] = $pending->count();
            $data['cheque_pending_total'] = (float) $pending->sum('amount');
            $data['cheque_overdue_count'] = $pending->filter(fn ($c) => $c->due_date->lt(now()->startOfDay()))->count();
            $data['cheque_dues'] = $pending->take(8);
        }

        if ($data['billiards_enabled']) {
            $today = app(BilliardsService::class)->todayBookingsQuery()->get();
            $data['billiards_today'] = $today->take(8);
            $data['billiards_today_count'] = $today->count();
            $data['billiards_active_count'] = $today->where('status', 'active')->count();
            $data['billiards_unpaid_count'] = BilliardBooking::query()
                ->whereIn('status', ['booked', 'active', 'completed'])
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereDate('scheduled_start', today())
                ->count();
            $data['billiards_ending_soon'] = app(BilliardsService::class)->endingSoonQuery()->get();
        }

        return view('admin.dashboard', $data);
    }

    private function getDateRange(string $range, ?string $from, ?string $to): array
    {
        switch ($range) {
            case 'today':
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            case 'yesterday':
                return [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
            case 'this_week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'this_month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'custom':
                return [
                    $from ? Carbon::parse($from)->startOfDay() : Carbon::today()->startOfDay(),
                    $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay(),
                ];
            default:
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
        }
    }

    private function getSalesChart($startDate, $endDate, ?int $branchId = null): array
    {
        $daysDiff = $startDate->diffInDays($endDate);

        if ($daysDiff > 60) {
            $dateExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as date_label");
            $groupExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m')");
        } else {
            $dateExpr = DB::raw("DATE(created_at) as date_label");
            $groupExpr = DB::raw("DATE(created_at)");
        }

        $sales = Order::select($dateExpr, DB::raw('SUM(total_amount) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->notVoid()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy($groupExpr)
            ->orderBy($groupExpr)
            ->get();

        return [
            'labels' => $sales->pluck('date_label'),
            'data' => $sales->pluck('total'),
        ];
    }
}
