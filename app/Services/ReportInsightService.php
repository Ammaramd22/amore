<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ReportInsightService
{
    /**
     * Build plain-language AI-style insights from report metrics.
     * Returns array of ['tone' => success|warn|info|tip, 'title' => ..., 'text' => ...]
     */
    public static function sales(array $summary, Collection $sales, Collection $byType, string $currency): array
    {
        $insights = [];
        $total = (float) ($summary['total_sales'] ?? 0);
        $orders = (int) ($summary['total_orders'] ?? 0);
        $avg = (float) ($summary['avg_order'] ?? 0);

        if ($orders === 0) {
            return [[
                'tone' => 'warn',
                'title' => 'No sales in this period',
                'text' => 'Try widening the date range, or check that orders are marked paid and not voided.',
            ]];
        }

        $best = $sales->sortByDesc('total')->first();
        $worst = $sales->sortBy('total')->first();
        if ($best) {
            $insights[] = [
                'tone' => 'success',
                'title' => 'Peak sales day',
                'text' => sprintf(
                    '%s led with %s %s across %d orders. Staff that day for peak hours and consider a promo on quieter days.',
                    $best->date,
                    $currency,
                    number_format((float) $best->total, 2),
                    (int) $best->orders
                ),
            ];
        }

        if ($worst && $best && $sales->count() > 1 && (float) $worst->total < (float) $best->total * 0.45) {
            $insights[] = [
                'tone' => 'warn',
                'title' => 'Soft day detected',
                'text' => sprintf(
                    '%s was weak (%s %s). Review staffing, weather, or run a limited-time offer to lift slow days.',
                    $worst->date,
                    $currency,
                    number_format((float) $worst->total, 2)
                ),
            ];
        }

        $topType = $byType->sortByDesc('total')->first();
        $lowType = $byType->filter(fn ($t) => ($t['orders'] ?? 0) > 0)->sortBy('share')->first();
        if ($topType) {
            $insights[] = [
                'tone' => 'info',
                'title' => $topType['label'].' dominates the mix',
                'text' => sprintf(
                    '%s is %.1f%% of revenue (%s %s). Protect capacity for this channel; don’t under-staff it.',
                    $topType['label'],
                    (float) $topType['share'],
                    $currency,
                    number_format((float) $topType['total'], 2)
                ),
            ];
        }
        if ($lowType && $topType && $lowType['type'] !== $topType['type'] && (float) $lowType['share'] < 15) {
            $insights[] = [
                'tone' => 'tip',
                'title' => 'Grow '.$lowType['label'],
                'text' => sprintf(
                    '%s is only %.1f%% of sales. QR table cards, packaging upgrades, or delivery partners can lift this channel.',
                    $lowType['label'],
                    (float) $lowType['share']
                ),
            ];
        }

        $insights[] = [
            'tone' => 'info',
            'title' => 'Average ticket',
            'text' => sprintf(
                'Average order is %s %s across %d bills. Upsell add-ons or combos if you want to push this above %s %s.',
                $currency,
                number_format($avg, 2),
                $orders,
                $currency,
                number_format($avg * 1.15, 2)
            ),
        ];

        // Trend: first half vs second half of period
        if ($sales->count() >= 4) {
            $half = (int) floor($sales->count() / 2);
            $first = $sales->take($half)->sum(fn ($s) => (float) $s->total);
            $second = $sales->slice($half)->sum(fn ($s) => (float) $s->total);
            if ($first > 0) {
                $change = (($second - $first) / $first) * 100;
                $insights[] = [
                    'tone' => $change >= 0 ? 'success' : 'warn',
                    'title' => $change >= 0 ? 'Sales trending up' : 'Sales trending down',
                    'text' => sprintf(
                        'Second half of the period is %s%.1f%% vs the first half (%s %s → %s %s).',
                        $change >= 0 ? '+' : '',
                        $change,
                        $currency,
                        number_format($first, 2),
                        $currency,
                        number_format($second, 2)
                    ),
                ];
            }
        }

        return array_slice($insights, 0, 5);
    }

    public static function products(array $summary, Collection $products, string $currency): array
    {
        if ($products->isEmpty()) {
            return [[
                'tone' => 'warn',
                'title' => 'No product movement',
                'text' => 'No items sold in this range. Confirm order items are recording product IDs.',
            ]];
        }

        $top = $products->first();
        $revenue = (float) ($summary['revenue'] ?? 0);
        $topShare = $revenue > 0 ? ((float) $top->revenue / $revenue) * 100 : 0;
        $insights = [[
            'tone' => 'success',
            'title' => 'Hero item: '.$top->name,
            'text' => sprintf(
                'Sold %s units for %s %s (%.1f%% of item revenue). Keep stock and kitchen prep ready for this SKU.',
                number_format((float) $top->qty, 0),
                $currency,
                number_format((float) $top->revenue, 2),
                $topShare
            ),
        ]];

        if ($products->count() >= 5) {
            $bottom = $products->sortBy('qty')->take(3);
            $insights[] = [
                'tone' => 'tip',
                'title' => 'Slow movers',
                'text' => 'Lowest qty: '.$bottom->pluck('name')->implode(', ').'. Bundle them, feature on QR menu, or retire if consistently low.',
            ];
        }

        $top3 = $products->take(3)->sum(fn ($p) => (float) $p->revenue);
        if ($revenue > 0 && ($top3 / $revenue) > 0.55) {
            $insights[] = [
                'tone' => 'warn',
                'title' => 'Revenue concentration risk',
                'text' => sprintf(
                    'Top 3 items are %.0f%% of revenue. Diversify promotions so sales aren’t dependent on a few dishes.',
                    ($top3 / $revenue) * 100
                ),
            ];
        }

        $insights[] = [
            'tone' => 'info',
            'title' => 'Catalogue breadth',
            'text' => sprintf(
                '%d products sold · %s units · %s %s revenue. Focus kitchen on the top sellers during rush.',
                (int) ($summary['skus'] ?? 0),
                number_format((float) ($summary['items_sold'] ?? 0), 0),
                $currency,
                number_format($revenue, 2)
            ),
        ];

        return $insights;
    }

    public static function customers(array $summary, Collection $customers, string $currency): array
    {
        if ($customers->isEmpty()) {
            return [[
                'tone' => 'warn',
                'title' => 'No identified customers',
                'text' => 'Capture phone numbers on POS / waiter panel to unlock loyalty insights.',
            ]];
        }

        $top = $customers->first();
        $spent = (float) ($summary['spent'] ?? 0);
        $count = (int) ($summary['customers'] ?? 0);
        $insights = [[
            'tone' => 'success',
            'title' => 'VIP: '.$top->name,
            'text' => sprintf(
                '%s spent %s %s over %d orders. Thank them with a loyalty offer or WhatsApp promo.',
                $top->name,
                $currency,
                number_format((float) ($top->orders_sum_total_amount ?? 0), 2),
                (int) $top->orders_count
            ),
        ]];

        $avgSpend = $count > 0 ? $spent / $count : 0;
        $insights[] = [
            'tone' => 'info',
            'title' => 'Customer value',
            'text' => sprintf(
                '%d active guests · avg spend %s %s. Aim to lift repeat visits with QR offers after payment.',
                $count,
                $currency,
                number_format($avgSpend, 2)
            ),
        ];

        $oneTimers = $customers->where('orders_count', 1)->count();
        if ($count > 0 && $oneTimers / $count > 0.5) {
            $insights[] = [
                'tone' => 'tip',
                'title' => 'Many one-visit guests',
                'text' => sprintf(
                    '%d of %d customers ordered once only (%.0f%%). Follow up with SMS/WhatsApp to drive return visits.',
                    $oneTimers,
                    $count,
                    ($oneTimers / $count) * 100
                ),
            ];
        }

        return $insights;
    }

    public static function stock(array $summary, Collection $ingredients, string $currency): array
    {
        $low = (int) ($summary['low'] ?? 0);
        $insights = [[
            'tone' => 'info',
            'title' => 'Inventory snapshot',
            'text' => sprintf(
                '%d ingredients valued at %s %s.',
                (int) ($summary['items'] ?? 0),
                $currency,
                number_format((float) ($summary['value'] ?? 0), 2)
            ),
        ]];

        if ($low > 0) {
            $names = $ingredients->filter(fn ($i) => method_exists($i, 'isLowStock') && $i->isLowStock())
                ->take(5)
                ->pluck('name')
                ->implode(', ');
            $insights[] = [
                'tone' => 'warn',
                'title' => $low.' item(s) low on stock',
                'text' => ($names ?: 'Some ingredients').' need reorder soon to avoid 86’ing menu items.',
            ];
        } else {
            $insights[] = [
                'tone' => 'success',
                'title' => 'Stock levels healthy',
                'text' => 'No low-stock alerts right now. Keep reviewing after busy weekends.',
            ];
        }

        $topValue = $ingredients->sortByDesc(fn ($i) => (float) $i->stock_quantity * (float) $i->cost_per_unit)->first();
        if ($topValue) {
            $insights[] = [
                'tone' => 'tip',
                'title' => 'Highest capital tied: '.$topValue->name,
                'text' => sprintf(
                    'About %s %s sitting in this ingredient. Avoid over-ordering perishables.',
                    $currency,
                    number_format((float) $topValue->stock_quantity * (float) $topValue->cost_per_unit, 2)
                ),
            ];
        }

        return $insights;
    }

    public static function waiters(array $summary, $ranking, $topWaiter, string $currency): array
    {
        $ranking = collect($ranking);
        if ($ranking->isEmpty()) {
            return [[
                'tone' => 'warn',
                'title' => 'No waiter sales tagged',
                'text' => 'Assign waiters on dine-in orders so you can coach and reward performance.',
            ]];
        }

        $insights = [];
        if ($topWaiter) {
            $insights[] = [
                'tone' => 'success',
                'title' => 'Top performer: '.$topWaiter['waiter_name'],
                'text' => sprintf(
                    '%s with %s %s. Share their upsell habits with the rest of the floor team.',
                    $topWaiter['waiter_name'],
                    $currency,
                    number_format((float) $topWaiter['total_sales'], 2)
                ),
            ];
        }

        $unassigned = (int) ($summary['without_waiter'] ?? 0);
        if ($unassigned > 0) {
            $insights[] = [
                'tone' => 'warn',
                'title' => 'Unassigned bills',
                'text' => $unassigned.' orders have no waiter. Fix assignment so commissions and ratings stay accurate.',
            ];
        }

        if (($summary['avg_rating'] ?? null) !== null) {
            $avg = (float) $summary['avg_rating'];
            $insights[] = [
                'tone' => $avg >= 4 ? 'success' : 'tip',
                'title' => 'Guest satisfaction',
                'text' => sprintf(
                    'Average rating %.1f★ from %d reviews. %s',
                    $avg,
                    (int) ($summary['ratings_count'] ?? 0),
                    $avg >= 4 ? 'Service quality looks strong — keep it consistent.' : 'Coach service recovery on low-rated tables.'
                ),
            ];
        }

        if ($ranking->count() >= 2) {
            $first = $ranking->first();
            $last = $ranking->last();
            if (($first['total_sales'] ?? 0) > 0 && ($last['total_sales'] ?? 0) < ($first['total_sales'] * 0.35)) {
                $insights[] = [
                    'tone' => 'tip',
                    'title' => 'Wide performance gap',
                    'text' => sprintf(
                        '%s leads while %s is far behind. Pair juniors with top waiters during rush.',
                        $first['waiter_name'],
                        $last['waiter_name']
                    ),
                ];
            }
        }

        return array_slice($insights, 0, 5);
    }

    public static function payments(float $total, int $txnCount, Collection $byMethod, int $splitCount, string $currency): array
    {
        if ($txnCount === 0) {
            return [[
                'tone' => 'warn',
                'title' => 'No payments recorded',
                'text' => 'No completed payment lines in this range.',
            ]];
        }

        $top = $byMethod->sortByDesc('total_amount')->first();
        $insights = [[
            'tone' => 'info',
            'title' => 'Preferred tender: '.($top['label'] ?? '—'),
            'text' => sprintf(
                '%s is %.0f%% of collections (%s %s). Ensure that channel’s terminal / drawer is always ready.',
                $top['label'] ?? '—',
                $total > 0 ? (($top['total_amount'] ?? 0) / $total) * 100 : 0,
                $currency,
                number_format((float) ($top['total_amount'] ?? 0), 2)
            ),
        ]];

        $cash = $byMethod->firstWhere('method', 'cash');
        if ($cash && $total > 0 && ($cash['total_amount'] / $total) > 0.6) {
            $insights[] = [
                'tone' => 'tip',
                'title' => 'Cash-heavy period',
                'text' => 'Over 60% cash. Tighten mid-shift counts and float checks to reduce variance risk.',
            ];
        }

        if ($splitCount > 0) {
            $insights[] = [
                'tone' => 'success',
                'title' => 'Multi-pay in use',
                'text' => $splitCount.' bills used 2+ methods — guests like flexibility; keep split pay enabled on POS.',
            ];
        }

        $insights[] = [
            'tone' => 'info',
            'title' => 'Collections',
            'text' => sprintf(
                '%s %s across %d payment lines (avg %s %s per line).',
                $currency,
                number_format($total, 2),
                $txnCount,
                $currency,
                number_format($txnCount > 0 ? $total / $txnCount : 0, 2)
            ),
        ];

        return $insights;
    }

    public static function shifts(array $summary, Collection $registers, string $currency): array
    {
        if ($registers->isEmpty()) {
            return [[
                'tone' => 'warn',
                'title' => 'No closed shifts',
                'text' => 'Close the register at end of day to unlock shift analytics.',
            ]];
        }

        $diff = (float) ($summary['difference'] ?? 0);
        $insights = [[
            'tone' => abs($diff) < 100 ? 'success' : 'warn',
            'title' => abs($diff) < 100 ? 'Cash variance under control' : 'Cash variance needs attention',
            'text' => sprintf(
                'Net difference %s %s across %d shifts. %s',
                $currency,
                number_format($diff, 2),
                (int) ($summary['shifts'] ?? 0),
                abs($diff) < 100 ? 'Keep dual-count discipline.' : 'Retrain closing checklist and spot-check oversized diffs.'
            ),
        ]];

        $best = $registers->sortByDesc(fn ($r) => (float) $r->total_sales)->first();
        if ($best) {
            $insights[] = [
                'tone' => 'success',
                'title' => 'Strongest shift',
                'text' => sprintf(
                    '%s closed %s %s sales (%d orders). Study that shift’s timing for roster planning.',
                    $best->user?->name ?? 'Cashier',
                    $currency,
                    number_format((float) $best->total_sales, 2),
                    (int) $best->orders_count
                ),
            ];
        }

        $worstDiff = $registers->sortBy(fn ($r) => (float) ($r->difference ?? 0))->first();
        if ($worstDiff && abs((float) ($worstDiff->difference ?? 0)) >= 200) {
            $insights[] = [
                'tone' => 'warn',
                'title' => 'Largest shortage / overage',
                'text' => sprintf(
                    'Shift #%d (%s) difference %s %s — review voids and cash drops for that close.',
                    $worstDiff->id,
                    $worstDiff->user?->name ?? '—',
                    $currency,
                    number_format((float) $worstDiff->difference, 2)
                ),
            ];
        }

        return $insights;
    }
}
