<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WaiterRating;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WaiterReportService
{
    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public static function resolveRange(string $range, ?string $from = null, ?string $to = null): array
    {
        return match ($range) {
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay(), 'yesterday'],
            'week', 'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'week'],
            'month', 'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'month'],
            'year', 'this_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear(), 'year'],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : Carbon::today()->startOfDay(),
                $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay(),
                'custom',
            ],
            default => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay(), 'today'],
        };
    }

    public static function build(Carbon $start, Carbon $end, ?int $branchId = null): array
    {
        $base = Order::query()
            ->notVoid()
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $summary = [
            'total_sales' => (float) (clone $base)->sum('total_amount'),
            'total_orders' => (int) (clone $base)->count(),
            'with_waiter' => (int) (clone $base)->whereNotNull('waiter_id')->count(),
            'without_waiter' => (int) (clone $base)->whereNull('waiter_id')->count(),
            'ratings_count' => (int) WaiterRating::whereBetween('rated_at', [$start, $end])->count(),
            'avg_rating' => WaiterRating::whereBetween('rated_at', [$start, $end])->avg('rating'),
        ];
        $summary['avg_rating'] = $summary['avg_rating'] !== null
            ? round((float) $summary['avg_rating'], 1)
            : null;
        $summary['avg_order'] = $summary['total_orders'] > 0
            ? $summary['total_sales'] / $summary['total_orders']
            : 0.0;

        $ratingByWaiter = WaiterRating::query()
            ->whereBetween('rated_at', [$start, $end])
            ->select([
                'waiter_id',
                DB::raw('COUNT(*) as ratings_count'),
                DB::raw('AVG(rating) as avg_rating'),
            ])
            ->groupBy('waiter_id')
            ->get()
            ->keyBy('waiter_id');

        $ranking = Order::query()
            ->from('orders')
            ->leftJoin('users', 'orders.waiter_id', '=', 'users.id')
            ->where('orders.is_void', false)
            ->whereBetween('orders.created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
            ->whereNotNull('orders.waiter_id')
            ->select([
                'orders.waiter_id',
                DB::raw("COALESCE(users.name, 'Unknown') as waiter_name"),
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(orders.total_amount) as total_sales'),
                DB::raw('AVG(orders.total_amount) as avg_order'),
                DB::raw("SUM(CASE WHEN orders.payment_status = 'paid' THEN orders.total_amount ELSE 0 END) as paid_sales"),
                DB::raw("SUM(CASE WHEN orders.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_orders"),
            ])
            ->groupBy('orders.waiter_id', 'users.name')
            ->orderByDesc('total_sales')
            ->get()
            ->values()
            ->map(function ($row, $index) use ($summary, $ratingByWaiter) {
                $sales = (float) $row->total_sales;
                $ratingRow = $ratingByWaiter->get($row->waiter_id);
                $avgRating = $ratingRow ? round((float) $ratingRow->avg_rating, 1) : null;
                $ratingsCount = $ratingRow ? (int) $ratingRow->ratings_count : 0;

                return [
                    'rank' => $index + 1,
                    'waiter_id' => (int) $row->waiter_id,
                    'waiter_name' => $row->waiter_name,
                    'orders_count' => (int) $row->orders_count,
                    'total_sales' => $sales,
                    'avg_order' => (float) $row->avg_order,
                    'paid_sales' => (float) $row->paid_sales,
                    'unpaid_orders' => (int) $row->unpaid_orders,
                    'share_pct' => $summary['total_sales'] > 0
                        ? round(($sales / $summary['total_sales']) * 100, 1)
                        : 0.0,
                    'avg_rating' => $avgRating,
                    'ratings_count' => $ratingsCount,
                    'rating_emoji' => $avgRating ? WaiterRating::emojiFor((int) round($avgRating)) : null,
                ];
            });

        $invoices = Order::with([
            'waiter:id,name',
            'cashier:id,name',
            'table:id,name',
            'kitchenOrders:id,order_id,kot_number,type',
            'waiterRating',
        ])
            ->notVoid()
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'payment_status' => $order->payment_status,
                'table' => $order->table?->name,
                'waiter_name' => $order->waiter?->name ?? '—',
                'cashier_name' => $order->cashier?->name ?? '—',
                'total' => (float) $order->total_amount,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'rating' => $order->waiterRating?->rating,
                'rating_emoji' => $order->waiterRating?->emoji,
                'kots' => $order->kitchenOrders->map(fn ($k) => [
                    'number' => $k->kot_number,
                    'type' => $k->type,
                    'waiter_name' => $order->waiter?->name ?? '—',
                ])->values(),
            ]);

        $recentRatings = WaiterRating::with(['waiter:id,name', 'order:id,order_number,table_id', 'order.table:id,name'])
            ->whereBetween('rated_at', [$start, $end])
            ->latest('rated_at')
            ->limit(50)
            ->get()
            ->map(fn (WaiterRating $r) => [
                'order_number' => $r->order?->order_number,
                'table' => $r->order?->table?->name,
                'waiter_name' => $r->waiter?->name ?? '—',
                'rating' => $r->rating,
                'emoji' => $r->emoji,
                'rated_at' => $r->rated_at?->format('Y-m-d H:i'),
            ]);

        $topWaiter = $ranking->first();

        return [
            'summary' => $summary,
            'ranking' => $ranking,
            'invoices' => $invoices,
            'recent_ratings' => $recentRatings,
            'top_waiter' => $topWaiter,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ];
    }
}
