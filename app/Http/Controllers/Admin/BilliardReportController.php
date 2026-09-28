<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BilliardBooking;
use App\Models\BilliardPayment;
use App\Models\BilliardTable;
use App\Models\Setting;
use App\Services\BilliardsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BilliardReportController extends Controller
{
    protected function ensureEnabled(): void
    {
        abort_unless(BilliardsService::enabled(), 403, 'Billiards module is disabled.');
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.reports') || auth()->user()?->can('reports.view') || auth()->user()?->isSoftwareOwner(), 403);

        $from = Carbon::parse($request->get('from', today()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->get('to', today()->toDateString()))->endOfDay();

        $bookings = BilliardBooking::query()
            ->with('table')
            ->whereBetween('scheduled_start', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $payments = BilliardPayment::query()
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $byMethod = $payments->groupBy('method')->map(fn ($rows) => round((float) $rows->sum('amount'), 2));
        $byTable = $bookings->groupBy(fn ($b) => $b->table?->name ?? '—')->map(function ($rows) {
            return [
                'bookings' => $rows->count(),
                'hours' => round((float) $rows->sum('hours'), 2),
                'revenue' => round((float) $rows->sum('amount'), 2),
                'collected' => round((float) $rows->sum('paid_amount'), 2),
            ];
        });

        $byType = BilliardTable::query()->get()->groupBy('type')->map(function ($tables) use ($bookings) {
            $ids = $tables->pluck('id');
            $rows = $bookings->whereIn('billiard_table_id', $ids);

            return [
                'bookings' => $rows->count(),
                'revenue' => round((float) $rows->sum('amount'), 2),
                'collected' => round((float) $rows->sum('paid_amount'), 2),
            ];
        });

        $daily = BilliardBooking::query()
            ->select(DB::raw('DATE(scheduled_start) as day'), DB::raw('COUNT(*) as bookings'), DB::raw('SUM(amount) as revenue'), DB::raw('SUM(paid_amount) as collected'))
            ->whereBetween('scheduled_start', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('admin.billiards.reports', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'totalBookings' => $bookings->count(),
            'totalHours' => round((float) $bookings->sum('hours'), 2),
            'totalRevenue' => round((float) $bookings->sum('amount'), 2),
            'totalCollected' => round((float) $payments->sum('amount'), 2),
            'unpaidTotal' => round((float) $bookings->sum(fn ($b) => $b->balanceDue()), 2),
            'byMethod' => $byMethod,
            'byTable' => $byTable,
            'byType' => $byType,
            'daily' => $daily,
            'activeNow' => BilliardBooking::query()->where('status', 'active')->count(),
        ]);
    }
}
