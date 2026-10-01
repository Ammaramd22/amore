<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Setting;
use App\Services\AccountService;
use App\Services\NetworkPrinterService;
use App\Services\RegisterReportMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function status()
    {
        $method = CashRegister::configuredMethod();
        $register = CashRegister::getActiveForPos(Auth::id());

        return response()->json([
            'shift_method' => $method,
            'labels' => $this->labels($method),
            'day_end_cutoff' => CashRegister::dayEndCutoff(),
            'past_day_end_cutoff' => CashRegister::isPastDayEndCutoff(),
            'has_open_register' => $register !== null,
            'register' => $register ? [
                'id' => $register->id,
                'mode' => $register->mode,
                'opened_at' => \App\Models\Setting::formatDateTime($register->opened_at, 'Y-m-d H:i:s'),
                'opening_balance' => $register->opening_balance,
                'cash_sales' => $register->cash_sales,
                'orders_count' => $register->orders_count,
                'business_date' => $register->business_date?->toDateString(),
            ] : null,
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $method = CashRegister::configuredMethod();

        if ($method === CashRegister::MODE_DAY_END) {
            if (CashRegister::getActiveForPos()) {
                return response()->json(['success' => false, 'message' => 'Day is already started'], 400);
            }

            $register = CashRegister::create([
                'user_id' => Auth::id(),
                'mode' => CashRegister::MODE_DAY_END,
                'business_date' => CashRegister::resolveBusinessDate(),
                'opening_balance' => $request->opening_balance,
                'notes' => $request->notes,
                'opened_at' => now(),
            ]);
        } else {
            if (CashRegister::getCurrentForUser(Auth::id())) {
                return response()->json(['success' => false, 'message' => 'You already have an open shift'], 400);
            }

            $register = CashRegister::create([
                'user_id' => Auth::id(),
                'mode' => CashRegister::MODE_SHIFT,
                'business_date' => CashRegister::resolveBusinessDate(),
                'opening_balance' => $request->opening_balance,
                'notes' => $request->notes,
                'opened_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'shift_method' => $method,
            'labels' => $this->labels($method),
            'register' => [
                'id' => $register->id,
                'mode' => $register->mode,
                'opened_at' => \App\Models\Setting::formatDateTime($register->opened_at, 'Y-m-d H:i:s'),
                'opening_balance' => $register->opening_balance,
            ],
        ]);
    }

    public function close(Request $request)
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'denominations' => 'nullable|array',
        ]);

        $register = CashRegister::getActiveForPos(Auth::id());
        if (! $register) {
            return response()->json(['success' => false, 'message' => 'No open register found'], 404);
        }

        // Shift mode: only the owner can close their shift
        if ($register->mode === CashRegister::MODE_SHIFT && (int) $register->user_id !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'This shift belongs to another cashier'], 403);
        }

        // Day End session (store-wide): cannot end the day while unpaid bills remain
        if ($register->mode === CashRegister::MODE_DAY_END) {
            $openBills = Order::openBillCountForDayEnd();
            if ($openBills > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Complete all open bills before day end ({$openBills} still open)",
                    'open_bills' => $openBills,
                ], 400);
            }
        }

        $notes = $register->notes ?? '';
        if ($request->notes) {
            $notes .= "\n".$request->notes;
        }
        if ($request->denominations) {
            $denomStr = "\nDenominations: ";
            foreach ($request->denominations as $value => $count) {
                if ($value === 'coins') {
                    $denomStr .= "Coins: LKR {$count}, ";
                } else {
                    $total = $value * $count;
                    $denomStr .= "{$count}x{$value}=".number_format($total, 2).', ';
                }
            }
            $notes .= rtrim($denomStr, ', ');
        }

        $register->update([
            'closing_balance' => $request->closing_balance,
            'notes' => $notes,
            'closed_at' => now(),
        ]);

        $register->refresh();

        if ($register->mode === CashRegister::MODE_DAY_END) {
            \App\Models\KitchenOrder::clearAllReady();
        }

        RegisterReportMailer::sendIfEnabled($register);

        $method = $register->mode;
        $payload = [
            'success' => true,
            'register_id' => $register->id,
            'print_url' => route('pos.register.print', $register).'?format=html',
            'shift_method' => $method,
            'labels' => $this->labels(CashRegister::configuredMethod()),
            'day_end' => null,
            'summary' => [
                'opening_balance' => (float) $register->opening_balance,
                'closing_balance' => (float) $register->closing_balance,
                'cash_sales' => (float) $register->cash_sales,
                'card_sales' => (float) $register->card_sales,
                'bank_transfer_sales' => (float) $register->bank_transfer_sales,
                'online_sales' => (float) $register->online_sales,
                'credit_sales' => (float) $register->credit_sales,
                'cash_in' => (float) $register->cash_in,
                'cash_out' => (float) $register->cash_out,
                'cash_refunds' => (float) ($register->cash_refunds ?? 0),
                'expected_cash' => (float) $register->expected_cash,
                'difference' => (float) $register->difference,
                'total_sales' => (float) $register->total_sales,
                'orders_count' => (int) $register->orders_count,
                'opened_at' => \App\Models\Setting::formatDateTime($register->opened_at, 'Y-m-d H:i:s'),
                'closed_at' => \App\Models\Setting::formatDateTime($register->closed_at, 'Y-m-d H:i:s'),
            ],
        ];

        // Shift mode after cutoff: ask cashier whether to end the day (do not auto-run)
        if ($register->mode === CashRegister::MODE_SHIFT && CashRegister::isPastDayEndCutoff()) {
            $date = $register->business_date?->toDateString() ?? CashRegister::resolveBusinessDate();
            $openLeft = CashRegister::openShiftCountForDate($date);
            $existingDay = CashRegister::dayEndForDate($date);
            $cutoff = CashRegister::dayEndCutoff();
            $openBills = Order::openBillCountForDayEnd();

            if ($openBills > 0) {
                $payload['day_end'] = [
                    'triggered' => false,
                    'ask' => false,
                    'pending' => true,
                    'open_bills' => $openBills,
                    'cutoff' => $cutoff,
                    'message' => "Past {$cutoff}. {$openBills} open bill(s) still unpaid — complete them before day end.",
                ];
            } elseif ($openLeft === 0 && ! $existingDay) {
                $shifts = CashRegister::shiftsForDate($date, true);
                $payload['day_end'] = [
                    'triggered' => false,
                    'ask' => true,
                    'cutoff' => $cutoff,
                    'business_date' => $date,
                    'closing_cash' => (float) $request->closing_balance,
                    'shifts_count' => $shifts->count(),
                    'preview_sales' => (float) $shifts->sum(fn (CashRegister $s) => (float) $s->total_sales),
                    'message' => 'Past '.$cutoff.'. End the day now and print the day-end report with all shifts?',
                ];
            } elseif ($openLeft > 0) {
                $payload['day_end'] = [
                    'triggered' => false,
                    'ask' => false,
                    'pending' => true,
                    'open_shifts' => $openLeft,
                    'cutoff' => $cutoff,
                    'message' => "Past {$cutoff}. {$openLeft} other shift(s) still open — day end can run when they close.",
                ];
            }
        }

        return response()->json($payload);
    }

    public function report()
    {
        try {
            $userId = Auth::id();
            if (! $userId) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $register = CashRegister::getActiveForPos($userId);
            if (! $register) {
                return response()->json(['success' => false, 'message' => 'No open register found'], 404);
            }

            $register->loadMissing('user');
            $method = $register->mode;

            try {
                $orders = Order::where('register_id', $register->id)
                    ->where('status', 'completed')
                    ->selectRaw('payment_method, SUM(total_amount) as total, COUNT(*) as count')
                    ->groupBy('payment_method')
                    ->get();
            } catch (\Exception $e) {
                $orders = collect([]);
            }

            $shiftDetails = [];
            if ($method === CashRegister::MODE_DAY_END) {
                // Include any closed shifts on the same day for the report preview
                $shiftDetails = CashRegister::with('user')
                    ->where('mode', CashRegister::MODE_SHIFT)
                    ->whereDate('business_date', $register->business_date ?? today())
                    ->whereNotNull('closed_at')
                    ->orderBy('opened_at')
                    ->get()
                    ->map(fn (CashRegister $s) => [
                        'id' => $s->id,
                        'cashier' => $s->user?->name,
                        'opened_at' => ($s->opened_at ? \App\Models\Setting::formatDateTime($s->opened_at, 'H:i') : null),
                        'closed_at' => ($s->closed_at ? \App\Models\Setting::formatDateTime($s->closed_at, 'H:i') : null),
                        'orders_count' => $s->orders_count,
                        'total_sales' => (float) $s->total_sales,
                        'opening_balance' => (float) $s->opening_balance,
                        'closing_balance' => (float) $s->closing_balance,
                        'difference' => (float) ($s->difference ?? 0),
                    ]);
            }

            return response()->json([
                'success' => true,
                'shift_method' => $method,
                'labels' => $this->labels($method),
                'register' => [
                    'id' => $register->id,
                    'mode' => $register->mode,
                    'cashier_name' => $register->user?->name ?? Auth::user()?->name,
                    'opened_at' => \App\Models\Setting::formatDateTime($register->opened_at, 'Y-m-d H:i:s'),
                    'opening_balance' => $register->opening_balance,
                    'cash_sales' => $register->cash_sales,
                    'card_sales' => $register->card_sales,
                    'bank_transfer_sales' => $register->bank_transfer_sales,
                    'online_sales' => $register->online_sales,
                    'credit_sales' => $register->credit_sales,
                    'cash_in' => $register->cash_in,
                    'cash_out' => $register->cash_out,
                    'cash_refunds' => (float) ($register->cash_refunds ?? 0),
                    'expected_cash' => $register->expected_cash,
                    'total_sales' => $register->total_sales,
                    'orders_count' => $register->orders_count,
                ],
                'payment_breakdown' => $orders,
                'cashier_breakdown' => $register->cashierBreakdown(),
                'shift_details' => $shiftDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cashMovement(Request $request)
    {
        $request->validate([
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $register = CashRegister::getActiveForPos(Auth::id());
        if (! $register) {
            return response()->json(['success' => false, 'message' => 'No open register found'], 404);
        }

        if ($request->type === 'in') {
            $register->increment('cash_in', $request->amount);
        } else {
            if ($register->expected_cash < $request->amount) {
                return response()->json(['success' => false, 'message' => 'Insufficient cash in register'], 400);
            }
            $register->increment('cash_out', $request->amount);
        }

        $cashAccount = Account::defaultCash();
        if ($cashAccount) {
            app(AccountService::class)->postCashMovement(
                $cashAccount,
                $request->type,
                (float) $request->amount,
                $request->reason
            );
        }

        return response()->json(['success' => true, 'message' => 'Cash movement recorded']);
    }

    public function history()
    {
        $method = CashRegister::configuredMethod();
        $query = CashRegister::whereNotNull('closed_at')->orderByDesc('closed_at')->limit(10);

        if ($method === CashRegister::MODE_DAY_END) {
            $query->where('mode', CashRegister::MODE_DAY_END);
        } else {
            $query->where('user_id', Auth::id())->where('mode', CashRegister::MODE_SHIFT);
        }

        $registers = $query->get([
            'id', 'mode', 'opened_at', 'closed_at', 'opening_balance', 'closing_balance', 'orders_count',
            'cash_sales', 'card_sales', 'bank_transfer_sales', 'online_sales', 'credit_sales',
        ]);

        return response()->json([
            'success' => true,
            'shift_method' => $method,
            'registers' => $registers->map(function (CashRegister $r) {
                return [
                    'id' => $r->id,
                    'mode' => $r->mode,
                    'opened_at' => $r->opened_at,
                    'closed_at' => $r->closed_at,
                    'opening_balance' => $r->opening_balance,
                    'closing_balance' => $r->closing_balance,
                    'total_sales' => $r->total_sales,
                    'orders_count' => $r->orders_count,
                ];
            }),
        ]);
    }

    public function openDrawer(NetworkPrinterService $printer)
    {
        $register = CashRegister::getActiveForPos(Auth::id());
        if (! $register) {
            return response()->json(['success' => false, 'message' => 'No open register'], 400);
        }

        $result = $printer->openCashDrawer();

        // activity() comes from spatie/laravel-activitylog — may be absent on some installs
        if (function_exists('\\activity')) {
            \activity()
                ->causedBy(Auth::user())
                ->performedOn($register)
                ->withProperties([
                    'success' => (bool) ($result['success'] ?? false),
                    'printer_ip' => $result['printer_ip'] ?? null,
                    'message' => $result['message'] ?? null,
                ])
                ->log('Cash drawer open requested');
        }

        // Always OK to return kick bytes for Local Print Bridge (USB)
        $hasPayload = ! empty($result['payload_base64']);

        return response()->json([
            'success' => (bool) ($result['success'] ?? false) || $hasPayload,
            'message' => $result['message'] ?? 'Cash drawer request failed',
            'payload_base64' => $result['payload_base64'] ?? null,
            'printer_name' => $result['printer_name'] ?? 'XP-80C',
            'needs_local_bridge' => (bool) ($result['needs_local_bridge'] ?? true),
        ]);
    }

    public function printReport(Request $request, CashRegister $register)
    {
        $user = Auth::user();
        if (! $user->can('reports.view') && (int) $register->user_id !== (int) $user->id) {
            // Day-end sessions can be printed by any authenticated POS user who can close the day
            if ($register->mode !== CashRegister::MODE_DAY_END) {
                abort(403);
            }
        }

        $register->loadMissing('user');
        $settings = Setting::getGroup('business');
        $topCategories = RegisterReportMailer::topCategories($register);
        $cashierBreakdown = $register->cashierBreakdown();
        $shiftDetails = collect();

        if ($register->mode === CashRegister::MODE_DAY_END) {
            $shiftDetails = CashRegister::shiftsForDate(
                $register->business_date?->toDateString() ?? $register->opened_at?->toDateString() ?? now()->toDateString(),
                false
            );
        }

        $view = $register->mode === CashRegister::MODE_DAY_END
            ? 'pos.day_end_report_80mm'
            : 'pos.shift_report_80mm';

        if ($request->query('format') === 'html') {
            return view($view, compact('register', 'settings', 'topCategories', 'cashierBreakdown', 'shiftDetails'));
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('register', 'settings', 'topCategories', 'cashierBreakdown', 'shiftDetails'))
            ->setPaper([0, 0, 226.77, 2000]);

        $prefix = $register->mode === CashRegister::MODE_DAY_END ? 'day-end' : 'shift';

        return $pdf->stream($prefix.'-report-'.$register->id.'.pdf');
    }

    /** Aggregate day-end report from all closed shifts on a date (shift method). */
    public function dayEndAggregate(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'opening_cash' => 'nullable|numeric|min:0',
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (CashRegister::usesDayEndMethod()) {
            return response()->json(['success' => false, 'message' => 'Use End Day on the open day session instead'], 400);
        }

        $date = $request->input('date', CashRegister::resolveBusinessDate());
        $openShifts = CashRegister::openShiftCountForDate($date);

        if ($openShifts > 0) {
            return response()->json([
                'success' => false,
                'message' => "Close all open shifts first ({$openShifts} still open)",
            ], 400);
        }

        $openBills = Order::openBillCountForDayEnd();
        if ($openBills > 0) {
            return response()->json([
                'success' => false,
                'message' => "Complete all open bills before day end ({$openBills} still open)",
                'open_bills' => $openBills,
            ], 400);
        }

        if (CashRegister::dayEndForDate($date)) {
            return response()->json(['success' => false, 'message' => 'Day end already recorded for '.$date], 400);
        }

        $shifts = CashRegister::shiftsForDate($date, true);
        if ($shifts->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No closed shifts found for this date'], 404);
        }

        $opening = $request->filled('opening_cash')
            ? (float) $request->opening_cash
            : null;

        try {
            $day = CashRegister::aggregateDayEnd(
                $date,
                (float) $request->closing_cash,
                $opening,
                Auth::id(),
                $request->notes
            );
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        // Clear leftover Ready notifications when the business day is closed
        \App\Models\KitchenOrder::clearAllReady();

        RegisterReportMailer::sendIfEnabled($day);

        return response()->json([
            'success' => true,
            'register_id' => $day->id,
            'print_url' => route('pos.register.print', $day).'?format=html',
            'shifts_count' => $shifts->count(),
            'shifts' => $shifts->map(fn (CashRegister $s) => [
                'id' => $s->id,
                'cashier' => $s->user?->name,
                'opened_at' => ($s->opened_at ? \App\Models\Setting::formatDateTime($s->opened_at, 'H:i') : null),
                'closed_at' => ($s->closed_at ? \App\Models\Setting::formatDateTime($s->closed_at, 'H:i') : null),
                'orders_count' => (int) $s->orders_count,
                'total_sales' => (float) $s->total_sales,
                'difference' => (float) ($s->difference ?? 0),
            ])->values(),
            'summary' => [
                'opening_balance' => (float) $day->opening_balance,
                'closing_balance' => (float) $day->closing_balance,
                'total_sales' => (float) $day->total_sales,
                'orders_count' => (int) $day->orders_count,
                'expected_cash' => (float) $day->expected_cash,
                'difference' => (float) $day->difference,
            ],
        ]);
    }

    protected function labels(string $method): array
    {
        return CashRegister::labels($method);
    }
}
