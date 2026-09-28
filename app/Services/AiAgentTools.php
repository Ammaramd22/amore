<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiAgentTools
{
    public static function currency(): string
    {
        return (string) Setting::get('currency_symbol', 'LKR');
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    public static function periodRange(string $period = 'today'): array
    {
        $tz = config('app.timezone', 'Asia/Colombo');
        $now = Carbon::now($tz);

        return match ($period) {
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'Yesterday'],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'This week'],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'This month'],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'Last month',
            ],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Today'],
        };
    }

    public static function salesSummary(string $period = 'today'): array
    {
        [$start, $end, $label] = self::periodRange($period);
        $currency = self::currency();

        $base = Order::whereBetween('created_at', [$start, $end])->notVoid();
        $completed = (clone $base)->completed();

        $sales = (float) $completed->sum('total_amount');
        $orders = (int) (clone $base)->count();
        $completedCount = (int) (clone $completed)->count();
        $avg = $completedCount > 0 ? $sales / $completedCount : 0;

        return [
            'period' => $label,
            'currency' => $currency,
            'total_sales' => round($sales, 2),
            'orders_count' => $orders,
            'completed_orders' => $completedCount,
            'average_order' => round($avg, 2),
            'dine_in' => (int) Order::whereBetween('created_at', [$start, $end])->notVoid()->byType('dine_in')->count(),
            'takeaway' => (int) Order::whereBetween('created_at', [$start, $end])->notVoid()->byType('takeaway')->count(),
            'delivery' => (int) Order::whereBetween('created_at', [$start, $end])->notVoid()->byType('delivery')->count(),
            'formatted' => sprintf(
                '%s: %s %s sales across %d completed orders (avg %s %s).',
                $label,
                $currency,
                number_format($sales, 2),
                $completedCount,
                $currency,
                number_format($avg, 2)
            ),
        ];
    }

    public static function topSellingProducts(string $period = 'today', int $limit = 10): array
    {
        [$start, $end, $label] = self::periodRange($period);
        $limit = max(1, min(25, $limit));
        $currency = self::currency();

        $rows = DB::table('order_items')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_sales')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.is_void', false)
            ->where('orders.status', 'completed')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        $items = $rows->map(fn ($r) => [
            'name' => $r->name,
            'quantity' => (float) $r->total_qty,
            'sales' => round((float) $r->total_sales, 2),
        ])->values()->all();

        return [
            'period' => $label,
            'currency' => $currency,
            'products' => $items,
            'formatted' => $items
                ? $label.' top sellers: '.collect($items)->take(5)->map(
                    fn ($p) => $p['name'].' ('.$p['quantity'].' sold, '.$currency.' '.number_format($p['sales'], 2).')'
                )->implode('; ')
                : 'No product sales in '.$label.'.',
        ];
    }

    public static function lowStock(): array
    {
        $ingredients = Ingredient::query()
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->active()
            ->orderBy('name')
            ->limit(30)
            ->get(['name', 'stock_quantity', 'reorder_level', 'unit']);

        $products = Product::query()
            ->where('track_stock', true)
            ->whereColumn('stock_quantity', '<=', DB::raw('0'))
            ->where('is_available', true)
            ->orderBy('name')
            ->limit(20)
            ->get(['name', 'stock_quantity']);

        $ingList = $ingredients->map(fn ($i) => [
            'type' => 'ingredient',
            'name' => $i->name,
            'stock' => (float) $i->stock_quantity,
            'reorder_level' => (float) $i->reorder_level,
            'unit' => $i->unit,
        ])->all();

        $prodList = $products->map(fn ($p) => [
            'type' => 'product',
            'name' => $p->name,
            'stock' => (float) $p->stock_quantity,
        ])->all();

        $all = array_merge($ingList, $prodList);

        return [
            'count' => count($all),
            'items' => $all,
            'formatted' => $all
                ? 'Low stock ('.count($all).'): '.collect($all)->take(15)->map(function ($i) {
                    if (($i['type'] ?? '') === 'ingredient') {
                        return $i['name'].' ('.$i['stock'].'/'.$i['reorder_level'].' '.$i['unit'].')';
                    }

                    return $i['name'].' (qty '.$i['stock'].')';
                })->implode('; ')
                : 'No low-stock items right now.',
        ];
    }

    public static function profitEstimate(string $period = 'this_month'): array
    {
        [$start, $end, $label] = self::periodRange($period);
        $currency = self::currency();

        $sales = (float) Order::whereBetween('created_at', [$start, $end])
            ->notVoid()
            ->completed()
            ->sum('total_amount');

        $cogs = (float) DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.is_void', false)
            ->where('orders.status', 'completed')
            ->selectRaw('COALESCE(SUM(order_items.quantity * products.cost_price), 0) as cogs')
            ->value('cogs');

        $expenses = 0.0;
        if (Schema::hasTable('expenses') && class_exists(Expense::class)) {
            try {
                $expenses = (float) Expense::query()
                    ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
                    ->sum('amount');
            } catch (\Throwable) {
                $expenses = 0.0;
            }
        }

        $gross = $sales - $cogs;
        $net = $gross - $expenses;

        return [
            'period' => $label,
            'currency' => $currency,
            'sales' => round($sales, 2),
            'estimated_cogs' => round($cogs, 2),
            'expenses' => round($expenses, 2),
            'gross_profit' => round($gross, 2),
            'net_profit_estimate' => round($net, 2),
            'note' => 'Profit uses product cost_price × qty sold. Exact COGS may differ if recipes/ingredients are used.',
            'formatted' => sprintf(
                '%s estimate: sales %s %s − COGS %s %s − expenses %s %s ≈ net %s %s.',
                $label,
                $currency,
                number_format($sales, 2),
                $currency,
                number_format($cogs, 2),
                $currency,
                number_format($expenses, 2),
                $currency,
                number_format($net, 2)
            ),
        ];
    }

    public static function customerHistory(string $query, int $limit = 10): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['error' => 'Provide a customer name or phone number.', 'customers' => []];
        }

        $currency = self::currency();
        $customers = Customer::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            })
            ->limit(5)
            ->get();

        if ($customers->isEmpty()) {
            return [
                'query' => $q,
                'customers' => [],
                'formatted' => 'No customer found for “'.$q.'”.',
            ];
        }

        $result = $customers->map(function (Customer $c) use ($currency, $limit) {
            $orders = $c->orders()
                ->notVoid()
                ->latest()
                ->limit($limit)
                ->get(['id', 'order_number', 'total_amount', 'status', 'created_at']);

            $totalSpent = (float) $c->orders()->notVoid()->completed()->sum('total_amount');

            return [
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'orders_count' => $c->orders()->notVoid()->count(),
                'total_spent' => round($totalSpent, 2),
                'recent_orders' => $orders->map(fn ($o) => [
                    'order_number' => $o->order_number,
                    'total' => (float) $o->total_amount,
                    'status' => $o->status,
                    'date' => optional($o->created_at)->format('Y-m-d H:i'),
                ])->all(),
                'formatted' => sprintf(
                    '%s (%s): %d orders, total %s %s.',
                    $c->name,
                    $c->phone ?: 'no phone',
                    $c->orders()->notVoid()->count(),
                    $currency,
                    number_format($totalSpent, 2)
                ),
            ];
        })->values()->all();

        return [
            'query' => $q,
            'currency' => $currency,
            'customers' => $result,
            'formatted' => collect($result)->pluck('formatted')->implode(' '),
        ];
    }

    public static function salesForecastNextMonth(): array
    {
        $tz = config('app.timezone', 'Asia/Colombo');
        $now = Carbon::now($tz);
        $currency = self::currency();

        $months = [];
        for ($i = 3; $i >= 1; $i--) {
            $start = $now->copy()->subMonthsNoOverflow($i)->startOfMonth();
            $end = $now->copy()->subMonthsNoOverflow($i)->endOfMonth();
            $sales = (float) Order::whereBetween('created_at', [$start, $end])
                ->notVoid()
                ->completed()
                ->sum('total_amount');
            $months[] = [
                'month' => $start->format('Y-m'),
                'label' => $start->format('M Y'),
                'sales' => round($sales, 2),
            ];
        }

        $values = array_column($months, 'sales');
        $avg = count($values) ? array_sum($values) / count($values) : 0.0;

        // Simple trend: last vs previous
        $trend = 0.0;
        if (count($values) >= 2 && $values[count($values) - 2] > 0) {
            $trend = ($values[count($values) - 1] - $values[count($values) - 2]) / $values[count($values) - 2];
        }

        $forecast = max(0, $avg * (1 + ($trend * 0.5)));
        $nextLabel = $now->copy()->addMonthNoOverflow()->format('M Y');

        return [
            'currency' => $currency,
            'history' => $months,
            'average_monthly' => round($avg, 2),
            'trend_pct' => round($trend * 100, 1),
            'forecast_next_month' => round($forecast, 2),
            'next_month' => $nextLabel,
            'note' => 'Simple estimate from the last 3 months (not a trained ML model).',
            'formatted' => sprintf(
                'Based on the last 3 months (avg %s %s, trend %+.1f%%), predicted %s sales ≈ %s %s.',
                $currency,
                number_format($avg, 2),
                $trend * 100,
                $nextLabel,
                $currency,
                number_format($forecast, 2)
            ),
        ];
    }

    public static function emailReport(string $period = 'today', ?string $to = null): array
    {
        $summary = self::salesSummary($period);
        $to = $to ?: (string) Setting::get('day_end_email_to', Setting::get('company_email', ''));
        if ($to === '') {
            return [
                'success' => false,
                'message' => 'No recipient email. Set day_end_email_to or company_email in settings, or pass to_email.',
            ];
        }

        $body = "Avenque AI Agent — Sales report\n\n"
            .($summary['formatted'] ?? '')."\n\n"
            .'Generated at '.now()->format('Y-m-d H:i');

        $ok = NotificationService::sendEmail(
            $to,
            'Sales report — '.$summary['period'],
            $body,
            'ai_report'
        );

        return [
            'success' => (bool) $ok,
            'to' => $to,
            'period' => $summary['period'],
            'message' => $ok ? 'Report emailed to '.$to : 'Email failed. Check SMTP settings.',
        ];
    }

    public static function sendWhatsAppBill(string $orderNumberOrId, ?string $phone = null): array
    {
        if (! NotificationService::whatsappConfigured()) {
            return [
                'success' => false,
                'message' => 'WhatsApp is not configured. Software owner must enable Meta WhatsApp in Integrations.',
            ];
        }

        $order = Order::query()
            ->with('customer')
            ->where(function ($q) use ($orderNumberOrId) {
                $q->where('order_number', $orderNumberOrId);
                if (ctype_digit((string) $orderNumberOrId)) {
                    $q->orWhere('id', (int) $orderNumberOrId);
                }
            })
            ->first();

        if (! $order) {
            return ['success' => false, 'message' => 'Order not found: '.$orderNumberOrId];
        }

        $phone = $phone ?: ($order->customer?->phone ?? '');
        if ($phone === '') {
            return ['success' => false, 'message' => 'No phone number on the order/customer. Pass phone explicitly.'];
        }

        $currency = self::currency();
        $msg = sprintf(
            "*%s*\nBill %s\nTotal: %s %s\nThank you!",
            Setting::get('company_name', 'QRPOS'),
            $order->order_number,
            $currency,
            number_format((float) $order->total_amount, 2)
        );

        $ok = NotificationService::sendWhatsApp($phone, $msg);

        return [
            'success' => (bool) $ok,
            'order_number' => $order->order_number,
            'phone' => $phone,
            'message' => $ok ? 'WhatsApp bill sent to '.$phone : 'WhatsApp send failed.',
        ];
    }
}
