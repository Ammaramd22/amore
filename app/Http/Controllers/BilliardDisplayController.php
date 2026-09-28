<?php

namespace App\Http\Controllers;

use App\Models\BilliardBooking;
use App\Models\BilliardTable;
use App\Models\Setting;
use App\Services\BilliardsService;
use Illuminate\Http\Request;

class BilliardDisplayController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);

        return view('admin.billiards.display', [
            'currency' => Setting::get('currency_symbol', 'LKR'),
            'company' => Setting::get('company_name', 'Billiards'),
            'endAlertMinutes' => BilliardsService::endAlertMinutes(),
            'feedUrl' => route('billiards.display.feed', $request->query()),
        ]);
    }

    public function feed(Request $request)
    {
        $this->guard($request);

        $alertMins = BilliardsService::endAlertMinutes();
        $tables = BilliardTable::query()->active()->ordered()->get();
        $sessions = BilliardBooking::query()
            ->with(['table', 'customer'])
            ->whereIn('status', ['booked', 'active'])
            ->whereDate('scheduled_start', '<=', today())
            ->where('scheduled_end', '>=', now()->subHour())
            ->orderBy('scheduled_end')
            ->get();

        $busyIds = $sessions->pluck('billiard_table_id')->unique()->all();

        $mapSession = function (BilliardBooking $b) use ($alertMins) {
            $end = $b->scheduled_end;
            $secs = $end ? (int) now()->diffInSeconds($end, false) : 0;

            return [
                'id' => $b->id,
                'number' => $b->booking_number,
                'table' => $b->table?->name ?? 'Table',
                'type' => $b->table?->typeLabel() ?? '',
                'customer' => $b->displayName(),
                'status' => $b->status,
                'hours' => (float) $b->hours,
                'starts_at' => $b->scheduled_start?->toIso8601String(),
                'ends_at' => $end?->toIso8601String(),
                'ends_ms' => $end ? $end->getTimestampMs() : null,
                'seconds_left' => $secs,
                'ending_soon' => $secs >= 0 && $secs <= ($alertMins * 60),
                'overtime' => $secs < 0,
            ];
        };

        return response()->json([
            'server_ms' => now()->getTimestampMs(),
            'alert_minutes' => $alertMins,
            'playing' => $sessions->where('status', 'active')->values()->map($mapSession),
            'booked' => $sessions->where('status', 'booked')->values()->map($mapSession),
            'free' => $tables->reject(fn ($t) => in_array($t->id, $busyIds, true))->values()->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'type' => $t->typeLabel(),
            ]),
        ]);
    }

    protected function guard(Request $request): void
    {
        abort_unless(BilliardsService::enabled(), 403, 'Billiards module is disabled.');

        $token = (string) $request->query('token', '');
        $expected = trim((string) Setting::get('billiards_display_token', ''));

        if ($expected !== '') {
            abort_unless(hash_equals($expected, $token), 403);
        }
    }
}
