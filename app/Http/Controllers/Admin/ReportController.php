<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\CashRegister;
use App\Models\Ingredient;
use App\Models\Payment;
use App\Models\LoyaltyStampLog;
use App\Services\LoyaltyService;
use App\Services\ReportInsightService;
use App\Services\WaiterReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected function branchFilter(Request $request): array
    {
        return \App\Services\BranchService::resolveFilter($request->get('branch_id'));
    }

    public function index()
    {
        return view('admin.reports.index');
    }

    public function sales(Request $request)
    {
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';
        $branchFilter = $this->branchFilter($request);
        $branchId = $branchFilter['id'];

        $base = Order::whereBetween('created_at', [$start, $end])->notVoid()
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) {
                $q->where('source', '!=', 'qr')
                    ->orWhere('approval_status', 'approved')
                    ->orWhereNull('approval_status');
            });
        \App\Services\BranchService::scopeOrders($base, $branchId);

        $hasPaid = (clone $base)->exists();
        $query = $hasPaid
            ? Order::whereBetween('created_at', [$start, $end])->notVoid()->where('payment_status', 'paid')
            : Order::whereBetween('created_at', [$start, $end])->notVoid();
        \App\Services\BranchService::scopeOrders($query, $branchId);

        $sales = (clone $query)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalSales = (float) (clone $query)->sum('total_amount');
        $totalOrders = (int) (clone $query)->count();

        $typeMeta = [
            'dine_in' => ['label' => 'Dine In', 'icon' => 'fa-utensils', 'color' => 'green'],
            'takeaway' => ['label' => 'Takeaway', 'icon' => 'fa-bag-shopping', 'color' => 'amber'],
            'delivery' => ['label' => 'Delivery', 'icon' => 'fa-motorcycle', 'color' => 'blue'],
            'express' => ['label' => 'Express', 'icon' => 'fa-bolt', 'color' => 'violet'],
        ];

        $typeTotals = (clone $query)
            ->select('order_type', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('order_type')
            ->get()
            ->keyBy(fn ($r) => $r->order_type ?: 'dine_in');

        $dailyByType = (clone $query)
            ->select(
                'order_type',
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('order_type', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($r) => $r->order_type ?: 'dine_in');

        $byType = collect(['dine_in', 'takeaway', 'delivery', 'express'])->map(function ($type) use ($typeMeta, $typeTotals, $dailyByType, $totalSales) {
            $meta = $typeMeta[$type];
            $tot = $typeTotals->get($type);
            $orders = (int) ($tot->orders ?? 0);
            $total = (float) ($tot->total ?? 0);
            $days = ($dailyByType->get($type) ?? collect())->values()->map(fn ($d) => [
                'date' => $d->date,
                'orders' => (int) $d->orders,
                'total' => (float) $d->total,
            ]);

            return [
                'type' => $type,
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'orders' => $orders,
                'total' => $total,
                'avg' => $orders > 0 ? $total / $orders : 0,
                'share' => $totalSales > 0 ? ($total / $totalSales) * 100 : 0,
                'days' => $days,
            ];
        })->filter(fn ($t) => $t['type'] !== 'express' || $t['orders'] > 0)->values();

        $summary = [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'avg_order' => $totalOrders > 0 ? $totalSales / $totalOrders : 0,
        ];

        $charts = [
            'trend' => [
                'labels' => $sales->pluck('date')->values(),
                'sales' => $sales->pluck('total')->map(fn ($v) => (float) $v)->values(),
                'orders' => $sales->pluck('orders')->map(fn ($v) => (int) $v)->values(),
            ],
            'types' => [
                'labels' => $byType->pluck('label')->values(),
                'data' => $byType->pluck('total')->values(),
                'colors' => ['#10b981', '#f59e0b', '#0ea5e9', '#8b5cf6'],
            ],
        ];

        $insights = ReportInsightService::sales($summary, $sales, $byType, $currency);

        return view('admin.reports.sales', compact('sales', 'summary', 'from', 'to', 'currency', 'byType', 'charts', 'insights', 'branchFilter'));
    }

    public function products(Request $request)
    {
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $branchFilter = $this->branchFilter($request);
        $branchId = $branchFilter['id'];

        $products = OrderItem::select('products.name', 'products.code', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.total_price) as revenue'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('order_items.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
            ->groupBy('products.id', 'products.name', 'products.code')
            ->orderByDesc('qty')
            ->get();

        $summary = [
            'items_sold' => (float) $products->sum('qty'),
            'revenue' => (float) $products->sum('revenue'),
            'skus' => $products->count(),
        ];

        $top = $products->take(8);
        $charts = [
            'qty' => [
                'labels' => $top->pluck('name')->values(),
                'data' => $top->pluck('qty')->map(fn ($v) => (float) $v)->values(),
            ],
            'revenue' => [
                'labels' => $top->pluck('name')->values(),
                'data' => $top->pluck('revenue')->map(fn ($v) => (float) $v)->values(),
            ],
        ];

        $insights = ReportInsightService::products($summary, $products, $currency);

        return view('admin.reports.products', compact('products', 'from', 'to', 'currency', 'summary', 'charts', 'insights', 'branchFilter'));
    }

    public function customers(Request $request)
    {
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $branchFilter = $this->branchFilter($request);
        $branchId = $branchFilter['id'];

        $customers = Customer::withCount(['orders' => function ($q) use ($from, $to, $branchId) {
            $q->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
            if ($branchId) { $q->where('branch_id', $branchId); }
        }])
            ->withSum(['orders' => function ($q) use ($from, $to, $branchId) {
                $q->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
                if ($branchId) { $q->where('branch_id', $branchId); }
            }], 'total_amount')
            ->having('orders_count', '>', 0)
            ->orderByDesc('orders_sum_total_amount')
            ->get();

        $summary = [
            'customers' => $customers->count(),
            'orders' => (int) $customers->sum('orders_count'),
            'spent' => (float) $customers->sum('orders_sum_total_amount'),
        ];

        $top = $customers->take(8);
        $charts = [
            'spend' => [
                'labels' => $top->pluck('name')->values(),
                'data' => $top->map(fn ($c) => (float) ($c->orders_sum_total_amount ?? 0))->values(),
            ],
            'orders' => [
                'labels' => $top->pluck('name')->values(),
                'data' => $top->pluck('orders_count')->map(fn ($v) => (int) $v)->values(),
            ],
        ];

        $insights = ReportInsightService::customers($summary, $customers, $currency);

        return view('admin.reports.customers', compact('customers', 'from', 'to', 'currency', 'summary', 'charts', 'insights', 'branchFilter'));
    }

    public function loyalty(Request $request)
    {
        if (! LoyaltyService::enabled()) {
            abort(403, 'Loyalty is disabled');
        }

        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $branchFilter = $this->branchFilter($request);
        $config = LoyaltyService::config();

        $members = Customer::query()
            ->loyaltyJoined()
            ->withCount([
                'loyaltyLogs as stamps_earned' => fn ($q) => $q->where('type', 'earn')
                    ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']),
                'loyaltyLogs as free_redeemed' => fn ($q) => $q->where('type', 'redeem')
                    ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']),
            ])
            ->orderByDesc('loyalty_stamps')
            ->orderByDesc('loyalty_free_drinks')
            ->orderBy('name')
            ->get();

        $redeems = LoyaltyStampLog::query()
            ->with('customer:id,name,phone')
            ->where('type', 'redeem')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->latest()
            ->limit(100)
            ->get();

        $earns = LoyaltyStampLog::query()
            ->where('type', 'earn')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get();

        $summary = [
            'members' => Customer::loyaltyJoined()->count(),
            'active_period' => $members->filter(fn ($m) => ($m->stamps_earned ?? 0) > 0 || ($m->free_redeemed ?? 0) > 0)->count(),
            'stamps' => (int) $earns->sum('stamps_delta'),
            'free_issued' => (int) $redeems->count(),
            'free_ready' => (int) Customer::loyaltyJoined()->sum('loyalty_free_drinks'),
            'expired' => Customer::loyaltyJoined()->whereNotNull('loyalty_expires_at')->where('loyalty_expires_at', '<', now())->count(),
        ];

        $top = $members->sortByDesc('free_redeemed')->take(8)->values();
        $charts = [
            'redeems' => [
                'labels' => $top->pluck('name')->values(),
                'data' => $top->pluck('free_redeemed')->map(fn ($v) => (int) $v)->values(),
            ],
            'stamps' => [
                'labels' => $members->sortByDesc('stamps_earned')->take(8)->pluck('name')->values(),
                'data' => $members->sortByDesc('stamps_earned')->take(8)->pluck('stamps_earned')->map(fn ($v) => (int) $v)->values(),
            ],
        ];

        $insights = [
            [
                'tone' => 'success',
                'title' => 'Free drinks issued',
                'text' => number_format($summary['free_issued']).' × '.$config['reward_label'].' redeemed in this period.',
            ],
            [
                'tone' => 'info',
                'title' => 'Stamps collected',
                'text' => number_format($summary['stamps']).' stamps earned across '.number_format($summary['active_period']).' active members.',
            ],
        ];

        return view('admin.reports.loyalty', compact(
            'members', 'redeems', 'from', 'to', 'currency', 'summary', 'charts', 'insights', 'config',
            'branchFilter'
        ));
    }

    public function stock(Request $request)
    {
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $branchFilter = $this->branchFilter($request);
        $branchId = $branchFilter['id'];
        $ingredients = Ingredient::with('stockMovements')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();
        $summary = [
            'items' => $ingredients->count(),
            'low' => $ingredients->filter(fn ($i) => method_exists($i, 'isLowStock') && $i->isLowStock())->count(),
            'value' => (float) $ingredients->sum(fn ($i) => (float) $i->stock_quantity * (float) $i->cost_per_unit),
        ];

        $valued = $ingredients->map(fn ($i) => [
            'name' => $i->name,
            'value' => (float) $i->stock_quantity * (float) $i->cost_per_unit,
            'qty' => (float) $i->stock_quantity,
            'low' => method_exists($i, 'isLowStock') && $i->isLowStock(),
        ])->sortByDesc('value')->values();

        $charts = [
            'value' => [
                'labels' => $valued->take(8)->pluck('name')->values(),
                'data' => $valued->take(8)->pluck('value')->values(),
            ],
            'status' => [
                'labels' => ['Healthy', 'Low stock'],
                'data' => [
                    max(0, $summary['items'] - $summary['low']),
                    $summary['low'],
                ],
            ],
        ];

        $insights = ReportInsightService::stock($summary, $ingredients, $currency);

        return view('admin.reports.stock', compact('ingredients', 'currency', 'summary', 'charts', 'insights', 'branchFilter'));
    }

    public function waiters(Request $request)
    {
        $range = $request->get('range', 'month');
        $fromInput = $request->get('from');
        $toInput = $request->get('to');
        $branchFilter = $this->branchFilter($request);

        if ($fromInput && $toInput && ! $request->has('range')) {
            $range = 'custom';
        }

        [$start, $end, $range] = WaiterReportService::resolveRange($range, $fromInput, $toInput);
        $report = WaiterReportService::build($start, $end, $branchFilter['id']);
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $ranking = collect($report['ranking']);

        $charts = [
            'sales' => [
                'labels' => $ranking->pluck('waiter_name')->values(),
                'data' => $ranking->pluck('total_sales')->map(fn ($v) => (float) $v)->values(),
            ],
            'orders' => [
                'labels' => $ranking->pluck('waiter_name')->values(),
                'data' => $ranking->pluck('orders_count')->map(fn ($v) => (int) $v)->values(),
            ],
        ];

        $insights = ReportInsightService::waiters($report['summary'], $ranking, $report['top_waiter'], $currency);

        return view('admin.reports.waiters', [
            'range' => $range,
            'from' => $report['from'],
            'to' => $report['to'],
            'summary' => $report['summary'],
            'ranking' => $report['ranking'],
            'invoices' => $report['invoices'],
            'recentRatings' => $report['recent_ratings'],
            'topWaiter' => $report['top_waiter'],
            'currency' => $currency,
            'charts' => $charts,
            'insights' => $insights,
            'branchFilter' => $branchFilter,
        ]);
    }

    public function payments(Request $request)
    {
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $branchFilter = $this->branchFilter($request);
        $branchId = $branchFilter['id'];

        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        $byMethod = Payment::query()
            ->select(
                'method',
                DB::raw('COUNT(*) as txn_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('branch_id', $branchId)))
            ->groupBy('method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'label' => match ($row->method) {
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'bank_transfer' => 'Bank',
                    'online' => 'Online',
                    'credit' => 'Credit',
                    'split' => 'Split',
                    default => ucfirst(str_replace('_', ' ', (string) $row->method)),
                },
                'txn_count' => (int) $row->txn_count,
                'total_amount' => (float) $row->total_amount,
            ]);

        $totalCollected = (float) $byMethod->sum('total_amount');
        $txnCount = (int) $byMethod->sum('txn_count');

        $daily = Payment::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('branch_id', $branchId)))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $splitOrders = Order::query()
            ->with(['payments', 'table', 'waiter:id,name'])
            ->where('is_void', false)
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('payments', fn ($q) => $q->where('status', 'completed'), '>=', 2)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function (Order $order) {
                $lines = $order->payments
                    ->where('status', 'completed')
                    ->map(fn ($p) => [
                        'method' => $p->method,
                        'amount' => (float) $p->amount,
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'table' => $order->table?->name,
                    'waiter' => $order->waiter?->name,
                    'total' => (float) $order->total_amount,
                    'paid' => (float) $order->paid_amount,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                    'lines' => $lines,
                ];
            });

        $charts = [
            'methods' => [
                'labels' => $byMethod->pluck('label')->values(),
                'data' => $byMethod->pluck('total_amount')->values(),
                'colors' => ['#10b981', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b'],
            ],
            'daily' => [
                'labels' => $daily->pluck('date')->values(),
                'data' => $daily->pluck('total')->map(fn ($v) => (float) $v)->values(),
            ],
        ];

        $insights = ReportInsightService::payments($totalCollected, $txnCount, $byMethod, $splitOrders->count(), $currency);

        return view('admin.reports.payments', compact('branchFilter', 
            'from',
            'to',
            'currency',
            'byMethod',
            'totalCollected',
            'txnCount',
            'splitOrders',
            'charts',
            'insights'
        ));
    }

    public function shifts(Request $request)
    {
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $branchFilter = $this->branchFilter($request);

        // KPIs / charts from individual shifts only (avoid double-count with day_end aggregates)
        $registers = CashRegister::with('user')
            ->where('mode', CashRegister::MODE_SHIFT)
            ->whereNotNull('closed_at')
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('business_date', [$from, $to])
                    ->orWhereBetween('closed_at', [$from.' 00:00:00', $to.' 23:59:59']);
            })
            ->orderByDesc('closed_at')
            ->get();

        $dayEnds = CashRegister::with('user')
            ->where('mode', CashRegister::MODE_DAY_END)
            ->whereNotNull('closed_at')
            ->whereBetween('business_date', [$from, $to])
            ->orderByDesc('business_date')
            ->get();

        $openRegisters = CashRegister::with('user')
            ->whereNull('closed_at')
            ->orderByDesc('opened_at')
            ->get();

        $summary = [
            'shifts' => $registers->count(),
            'day_ends' => $dayEnds->count(),
            'total_sales' => $registers->sum(fn ($r) => (float) $r->total_sales),
            'cash_sales' => $registers->sum(fn ($r) => (float) $r->cash_sales),
            'orders' => $registers->sum(fn ($r) => (int) $r->orders_count),
            'difference' => $registers->sum(fn ($r) => (float) ($r->difference ?? 0)),
        ];

        $byDate = $registers->groupBy(fn ($r) => $r->business_date?->toDateString() ?? $r->closed_at?->toDateString());

        $chronological = $registers->sortBy('closed_at')->values();
        $charts = [
            'sales' => [
                'labels' => $chronological->map(fn ($r) => ($r->closed_at?->format('m/d') ?? '#'.$r->id).' '.($r->user?->name ?? ''))->values(),
                'data' => $chronological->map(fn ($r) => (float) $r->total_sales)->values(),
            ],
            'diff' => [
                'labels' => $chronological->map(fn ($r) => '#'.$r->id)->values(),
                'data' => $chronological->map(fn ($r) => (float) ($r->difference ?? 0))->values(),
            ],
        ];

        $insights = ReportInsightService::shifts($summary, $registers, $currency);

        return view('admin.reports.shifts', compact(
            'branchFilter', 
            'from',
            'to',
            'currency',
            'registers',
            'dayEnds',
            'byDate',
            'openRegisters',
            'summary',
            'charts',
            'insights'
        ));
    }

    public function dayEnds(Request $request)
    {
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $branchFilter = $this->branchFilter($request);

        $dayEnds = CashRegister::with('user')
            ->where('mode', CashRegister::MODE_DAY_END)
            ->whereNotNull('closed_at')
            ->whereBetween('business_date', [$from, $to])
            ->orderByDesc('business_date')
            ->orderByDesc('id')
            ->get();

        $dates = $dayEnds->map(fn ($d) => $d->business_date?->toDateString())->filter()->unique()->values();

        $shiftsByDate = CashRegister::with('user')
            ->where('mode', CashRegister::MODE_SHIFT)
            ->whereNotNull('closed_at')
            ->when($dates->isNotEmpty(), fn ($q) => $q->whereIn('business_date', $dates->all()))
            ->when($dates->isEmpty(), fn ($q) => $q->whereBetween('business_date', [$from, $to]))
            ->orderBy('opened_at')
            ->get()
            ->groupBy(fn ($s) => $s->business_date?->toDateString());

        // Also include days that have shifts but no formal day_end row yet
        $orphanShifts = CashRegister::with('user')
            ->where('mode', CashRegister::MODE_SHIFT)
            ->whereNotNull('closed_at')
            ->whereBetween('business_date', [$from, $to])
            ->when($dates->isNotEmpty(), fn ($q) => $q->whereNotIn('business_date', $dates->all()))
            ->orderBy('opened_at')
            ->get()
            ->groupBy(fn ($s) => $s->business_date?->toDateString());

        $summary = [
            'days' => $dayEnds->count(),
            'total_sales' => $dayEnds->sum(fn ($r) => (float) $r->total_sales),
            'cash_sales' => $dayEnds->sum(fn ($r) => (float) $r->cash_sales),
            'orders' => $dayEnds->sum(fn ($r) => (int) $r->orders_count),
            'difference' => $dayEnds->sum(fn ($r) => (float) ($r->difference ?? 0)),
            'avg_sales' => $dayEnds->count() > 0
                ? $dayEnds->sum(fn ($r) => (float) $r->total_sales) / $dayEnds->count()
                : 0,
        ];

        $chronological = $dayEnds->sortBy('business_date')->values();
        $charts = [
            'sales' => [
                'labels' => $chronological->map(fn ($r) => $r->business_date?->format('m/d') ?? '#'.$r->id)->values(),
                'data' => $chronological->map(fn ($r) => (float) $r->total_sales)->values(),
            ],
            'orders' => [
                'labels' => $chronological->map(fn ($r) => $r->business_date?->format('m/d') ?? '#'.$r->id)->values(),
                'data' => $chronological->map(fn ($r) => (int) $r->orders_count)->values(),
            ],
        ];

        $insights = [];
        if ($dayEnds->isEmpty()) {
            $insights[] = [
                'tone' => 'warn',
                'title' => 'No day-end closes yet',
                'text' => 'Close the last shift after the day-end cutoff, or use POS → Day End (all shifts). Then this report lists every day with all cashier shifts.',
            ];
        } else {
            $best = $dayEnds->sortByDesc(fn ($r) => (float) $r->total_sales)->first();
            $insights[] = [
                'tone' => 'success',
                'title' => 'Strongest day',
                'text' => sprintf(
                    '%s recorded %s %s across %d orders (%d day closes in range).',
                    $best->business_date?->format('d M Y') ?? '—',
                    $currency,
                    number_format((float) $best->total_sales, 2),
                    (int) $best->orders_count,
                    $dayEnds->count()
                ),
            ];
            if ($orphanShifts->isNotEmpty()) {
                $insights[] = [
                    'tone' => 'warn',
                    'title' => 'Days without day-end close',
                    'text' => $orphanShifts->count().' business day(s) have closed shifts but no day-end aggregate yet.',
                ];
            }
        }

        return view('admin.reports.day-ends', compact(
            'branchFilter', 
            'from',
            'to',
            'currency',
            'dayEnds',
            'shiftsByDate',
            'orphanShifts',
            'summary',
            'charts',
            'insights'
        ));
    }

    public function cancelledBills(Request $request)
    {
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());
        $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';
        $branchFilter = $this->branchFilter($request);
        $branchId = $branchFilter['id'];
        $typeFilter = $request->get('void_type'); // void|cancel|all

        $query = Order::query()
            ->with(['table', 'waiter', 'voidedBy', 'cashier'])
            ->whereBetween('updated_at', [$start, $end])
            ->where(function ($q) {
                $q->where('is_void', true)
                    ->orWhere('status', 'cancelled');
            });

        if (in_array($typeFilter, ['void', 'cancel'], true)) {
            $query->where('void_type', $typeFilter);
        }

        \App\Services\BranchService::scopeOrders($query, $branchId);

        $orders = (clone $query)->latest('updated_at')->paginate(50)->withQueryString();

        $summary = [
            'total' => (clone $query)->count(),
            'void_count' => (clone $query)->where('void_type', 'void')->count(),
            'cancel_count' => (clone $query)->where('void_type', 'cancel')->count(),
            'amount' => (float) (clone $query)->sum('total_amount'),
        ];

        $insights = [];
        if ($summary['total'] > 0) {
            $insights[] = [
                'tone' => 'warn',
                'title' => 'Cancelled / voided bills',
                'text' => $summary['total'].' bill(s) in this period — '
                    .$summary['cancel_count'].' cancelled, '
                    .$summary['void_count'].' voided (amount '.$currency.' '.number_format($summary['amount'], 2).').',
            ];
        } else {
            $insights[] = [
                'tone' => 'ok',
                'title' => 'No cancelled bills',
                'text' => 'No voided or cancelled bills in the selected date range.',
            ];
        }

        return view('admin.reports.cancelled-bills', compact(
            'branchFilter',
            'from',
            'to',
            'currency',
            'orders',
            'summary',
            'insights',
            'typeFilter'
        ));
    }
}
