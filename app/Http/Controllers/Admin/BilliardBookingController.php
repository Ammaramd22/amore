<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BilliardBooking;
use App\Models\BilliardPayment;
use App\Models\BilliardTable;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\BilliardsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BilliardBookingController extends Controller
{
    public function __construct(protected BilliardsService $billiards)
    {
        //
    }

    protected function ensureEnabled(): void
    {
        abort_unless(BilliardsService::enabled(), 403, 'Billiards module is disabled. Ask the software owner to enable it.');
    }

    public function desk()
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        $tables = BilliardTable::query()->active()->ordered()->get();
        $today = $this->billiards->todayBookingsQuery()->get();
        $endingSoon = $this->billiards->endingSoonQuery()->get();
        $unpaid = BilliardBooking::query()
            ->with(['table', 'customer'])
            ->whereIn('status', ['booked', 'active', 'completed'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereDate('scheduled_start', '>=', today()->subDays(7))
            ->orderByDesc('scheduled_start')
            ->limit(40)
            ->get();

        $busyByTable = $today->whereIn('status', ['booked', 'active'])->keyBy('billiard_table_id');
        $freeCount = $tables->filter(fn ($t) => ! $busyByTable->has($t->id))->count();
        $busyCount = $tables->filter(fn ($t) => $busyByTable->has($t->id))->count();
        $todayRevenue = round((float) $today->sum('paid_amount'), 2);
        $todayBooked = round((float) $today->sum('amount'), 2);
        $todayHours = round((float) $today->sum('hours'), 2);
        $avgHours = $today->count() ? round((float) $today->avg('hours'), 2) : 0.0;
        $utilization = $tables->count() > 0 ? (int) round(($busyCount / $tables->count()) * 100) : 0;

        $chartLabels = [];
        $chartRevenue = [];
        $chartSessions = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $chartLabels[] = $day->format('D j');
            $dayAgg = BilliardBooking::query()
                ->whereDate('scheduled_start', $day->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->selectRaw('COUNT(*) as sessions, COALESCE(SUM(paid_amount), 0) as revenue')
                ->first();
            $chartSessions[] = (int) ($dayAgg->sessions ?? 0);
            $chartRevenue[] = round((float) ($dayAgg->revenue ?? 0), 2);
        }

        $poolCount = $today->filter(fn ($b) => ($b->table?->type ?? 'pool') === 'pool')->count();
        $snookerCount = $today->filter(fn ($b) => ($b->table?->type ?? '') === 'snooker')->count();
        $sourceMix = collect(BilliardBooking::SOURCES)->mapWithKeys(function ($src) use ($today) {
            return [$src => $today->where('source', $src)->count()];
        })->all();

        $busiestTable = null;
        $byTable = $today->groupBy('billiard_table_id')->map->count()->sortDesc();
        if ($byTable->isNotEmpty()) {
            $topId = (int) $byTable->keys()->first();
            $topTable = $tables->firstWhere('id', $topId) ?? BilliardTable::find($topId);
            if ($topTable) {
                $busiestTable = ['name' => $topTable->name, 'count' => (int) $byTable->first()];
            }
        }

        $weekRevenue = array_sum($chartRevenue);
        $insights = [];
        if ($endingSoon->isNotEmpty()) {
            $first = $endingSoon->first();
            $insights[] = [
                'tone' => 'warn',
                'icon' => 'fa-clock',
                'title' => 'Ending soon',
                'body' => ($first->table?->name ?? 'A table').' with '.$first->displayName().' wraps in about '.$first->minutesRemaining().' min — prep the next booking.',
            ];
        }
        if ($unpaid->isNotEmpty()) {
            $due = round($unpaid->sum(fn ($b) => $b->balanceDue()), 0);
            $insights[] = [
                'tone' => 'danger',
                'icon' => 'fa-hand-holding-usd',
                'title' => 'Cash to collect',
                'body' => $unpaid->count().' open bill'.($unpaid->count() === 1 ? '' : 's').' · '.Setting::get('currency_symbol', 'LKR').' '.number_format($due, 0).' still due.',
            ];
        }
        if ($busiestTable) {
            $insights[] = [
                'tone' => 'ok',
                'icon' => 'fa-trophy',
                'title' => 'Busiest table today',
                'body' => $busiestTable['name'].' leads with '.$busiestTable['count'].' session'.($busiestTable['count'] === 1 ? '' : 's').'.',
            ];
        }
        if ($tables->count() > 0) {
            $insights[] = [
                'tone' => $utilization >= 70 ? 'warn' : 'info',
                'icon' => 'fa-chart-pie',
                'title' => 'Floor load '.$utilization.'%',
                'body' => $busyCount.' of '.$tables->count().' tables in play'
                    .($avgHours > 0 ? ' · avg session '.$avgHours.'h' : '')
                    .($todayHours > 0 ? ' · '.$todayHours.'h booked today' : '').'.',
            ];
        }
        if ($weekRevenue > 0) {
            $insights[] = [
                'tone' => 'info',
                'icon' => 'fa-lightbulb',
                'title' => '7-day take',
                'body' => Setting::get('currency_symbol', 'LKR').' '.number_format($weekRevenue, 0).' collected across the last week — watch the trend chart for soft days.',
            ];
        }
        if ($insights === []) {
            $insights[] = [
                'tone' => 'info',
                'icon' => 'fa-magic',
                'title' => 'Ready when you are',
                'body' => 'No sessions yet today. Tap a free table or New booking to open the floor.',
            ];
        }

        return view('admin.billiards.desk', [
            'tables' => $tables,
            'today' => $today,
            'endingSoon' => $endingSoon,
            'unpaid' => $unpaid,
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'endAlertMinutes' => BilliardsService::endAlertMinutes(),
            'smsReady' => \App\Services\NotificationService::smsConfigured(),
            'freeCount' => $freeCount,
            'busyCount' => $busyCount,
            'todayRevenue' => $todayRevenue,
            'todayBooked' => $todayBooked,
            'todayHours' => $todayHours,
            'utilization' => $utilization,
            'revenue_chart' => [
                'labels' => $chartLabels,
                'revenue' => $chartRevenue,
                'sessions' => $chartSessions,
            ],
            'poolCount' => $poolCount,
            'snookerCount' => $snookerCount,
            'sourceMix' => $sourceMix,
            'insights' => $insights,
            'weekRevenue' => $weekRevenue,
        ]);
    }

    /** POS-style touch booking screen */
    public function pos(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.bookings') || auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        $tables = BilliardTable::query()->active()->ordered()->get();
        $today = $this->billiards->todayBookingsQuery()->get();
        $endingSoon = $this->billiards->endingSoonQuery()->get();
        $unpaid = BilliardBooking::query()
            ->with(['table', 'customer'])
            ->whereIn('status', ['booked', 'active', 'completed'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereDate('scheduled_start', '>=', today()->subDays(7))
            ->orderByDesc('scheduled_start')
            ->limit(20)
            ->get();

        return view('billiards.pos', [
            'tables' => $tables,
            'today' => $today,
            'endingSoon' => $endingSoon,
            'unpaid' => $unpaid,
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'endAlertMinutes' => BilliardsService::endAlertMinutes(),
            'smsReady' => \App\Services\NotificationService::smsConfigured(),
            'printAsk' => (bool) Setting::get('billiards_print_ask', true),
            'smsOnPay' => (bool) Setting::get('billiards_sms_on_pay', false),
            'autoStartOnPay' => (bool) Setting::get('billiards_auto_start_on_pay', true),
        ]);
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.bookings') || auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        $q = BilliardBooking::query()->with(['table', 'customer', 'creator'])->latest('scheduled_start');

        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($pay = $request->get('payment_status')) {
            $q->where('payment_status', $pay);
        }
        if ($date = $request->get('date')) {
            $q->whereDate('scheduled_start', $date);
        }
        if ($search = trim((string) $request->get('q'))) {
            $q->where(function ($w) use ($search) {
                $w->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        return view('admin.billiards.bookings.index', [
            'bookings' => $q->paginate(25)->withQueryString(),
            'currency' => Setting::get('currency_symbol', 'LKR'),
        ]);
    }

    public function create(Request $request)
    {
        return $this->pos($request);
    }

    public function store(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.bookings') || auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        $data = $request->validate([
            'billiard_table_id' => ['required', 'exists:billiard_tables,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'create_customer' => ['nullable', 'boolean'],
            'source' => ['required', 'in:walk_in,phone,online,admin'],
            'scheduled_start' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'start_now' => ['nullable', 'boolean'],
        ]);

        try {
            $booking = $this->billiards->createBooking($data, auth()->id());
            if ($request->boolean('start_now')) {
                $booking = $this->billiards->startSession($booking);
            }
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['billiard_table_id' => $e->getMessage()]);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['ok' => true, 'booking' => $booking->fresh(['table', 'customer'])]);
        }

        return redirect()->route('billiards.bookings.show', $booking)->with('success', 'Booking created.');
    }

    public function show(BilliardBooking $booking)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.bookings') || auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        $booking->load(['table', 'customer', 'payments.creator', 'creator']);

        return view('admin.billiards.bookings.show', [
            'booking' => $booking,
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'smsReady' => \App\Services\NotificationService::smsConfigured(),
        ]);
    }

    public function start(BilliardBooking $booking)
    {
        $this->ensureEnabled();
        try {
            $this->billiards->startSession($booking);
        } catch (\Throwable $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Session started.');
    }

    public function complete(Request $request, BilliardBooking $booking)
    {
        $this->ensureEnabled();
        $data = $request->validate([
            'hours' => ['nullable', 'numeric', 'min:0.25', 'max:24'],
        ]);
        try {
            $this->billiards->completeSession($booking, isset($data['hours']) ? (float) $data['hours'] : null);
        } catch (\Throwable $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Session completed.');
    }

    public function cancel(BilliardBooking $booking)
    {
        $this->ensureEnabled();
        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return back()->withErrors(['status' => 'Cannot cancel this booking.']);
        }
        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking cancelled.');
    }

    public function pay(Request $request, BilliardBooking $booking)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.pay') || auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        $data = $request->validate([
            'method' => ['required', 'in:'.implode(',', BilliardPayment::METHODS)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = $this->billiards->recordPayment(
                $booking,
                $data['method'],
                (float) $data['amount'],
                $data['reference'] ?? null,
                auth()->id(),
                $data['notes'] ?? null
            );
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'payment' => $payment,
                'booking' => $booking->fresh(['table', 'customer', 'payments']),
            ]);
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function sendReminder(BilliardBooking $booking)
    {
        $this->ensureEnabled();
        if ($booking->payment_status === 'paid') {
            return back()->withErrors(['reminder' => 'Already paid.']);
        }
        $ok = $this->billiards->sendPaymentReminder($booking);

        return back()->with($ok ? 'success' : 'error', $ok ? 'Reminder SMS sent.' : 'Could not send SMS. Check Integrations settings.');
    }

    public function sendEbill(BilliardBooking $booking)
    {
        $this->ensureEnabled();
        $ok = $this->billiards->sendEbillSms($booking);

        return back()->with($ok ? 'success' : 'error', $ok ? 'eBill SMS sent.' : 'Could not send eBill SMS.');
    }

    public function printReceipt(Request $request, BilliardBooking $booking)
    {
        $this->ensureEnabled();
        $booking->load(['table', 'customer', 'payments.creator']);

        $isHtml = $request->get('format') === 'html';
        $settings = [
            'company_name' => Setting::get('company_name', 'Restaurant'),
            'company_address' => Setting::get('company_address', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'receipt_footer' => Setting::get(
                'billiards_receipt_footer',
                'Thanks for playing! See you at the tables.'
            ),
            // HTML preview: public URL (fast). PDF: small data URI (DomPDF-safe).
            'invoice_logo_src' => $isHtml
                ? (Setting::invoiceLogoUrl() ?: Setting::invoiceLogoDataUri())
                : Setting::invoiceLogoDataUri(),
        ];

        if ($isHtml) {
            return response()
                ->view('admin.billiards.receipt_80mm', compact('booking', 'settings'))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $pdf = Pdf::loadView('admin.billiards.receipt_80mm', compact('booking', 'settings'))
            ->setPaper([0, 0, 226.77, 2000]);

        return $pdf->stream('billiards-'.$booking->booking_number.'.pdf');
    }

    public function customerLookup(Request $request)
    {
        $this->ensureEnabled();
        $q = trim((string) $request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $rows = Customer::query()
            ->active()
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone']);

        return response()->json($rows);
    }

    public function alertsJson()
    {
        $this->ensureEnabled();
        $ending = $this->billiards->endingSoonQuery()->get()->map(function (BilliardBooking $b) {
            return [
                'id' => $b->id,
                'number' => $b->booking_number,
                'table' => $b->table?->name,
                'customer' => $b->displayName(),
                'ends_at' => $b->scheduled_end?->toIso8601String(),
                'minutes' => $b->minutesRemaining(),
            ];
        });

        return response()->json([
            'ending_soon' => $ending,
            'alert_minutes' => BilliardsService::endAlertMinutes(),
        ]);
    }

    public function availability(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(auth()->user()?->can('billiards.bookings') || auth()->user()?->can('billiards.access') || auth()->user()?->isSoftwareOwner(), 403);

        $data = $request->validate([
            'billiard_table_id' => ['required', 'exists:billiard_tables,id'],
            'scheduled_start' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'ignore_booking_id' => ['nullable', 'integer'],
        ]);

        $table = BilliardTable::query()->findOrFail($data['billiard_table_id']);
        $hours = max(0.25, (float) $data['hours']);
        $start = \Carbon\Carbon::parse($data['scheduled_start']);
        $end = $start->copy()->addMinutes((int) round($hours * 60));
        $available = $table->isAvailableBetween(
            $start,
            $end,
            isset($data['ignore_booking_id']) ? (int) $data['ignore_booking_id'] : null
        );

        return response()->json([
            'available' => $available,
            'message' => $available
                ? 'Table is free for this time.'
                : 'Already booked — this table is taken for the selected time.',
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ]);
    }
}
