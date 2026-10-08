<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CashRegister extends Model
{
    use HasFactory;

    public const MODE_SHIFT = 'shift';

    public const MODE_DAY_END = 'day_end';

    protected $fillable = [
        'user_id',
        'mode',
        'business_date',
        'opening_balance',
        'closing_balance',
        'cash_sales',
        'card_sales',
        'bank_transfer_sales',
        'online_sales',
        'credit_sales',
        'cash_in',
        'cash_out',
        'cash_refunds',
        'orders_count',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'card_sales' => 'decimal:2',
        'bank_transfer_sales' => 'decimal:2',
        'online_sales' => 'decimal:2',
        'credit_sales' => 'decimal:2',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'cash_refunds' => 'decimal:2',
        'business_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'register_id');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    public function isDayEnd(): bool
    {
        return $this->mode === self::MODE_DAY_END;
    }

    public function getExpectedCashAttribute()
    {
        $refunds = (float) ($this->cash_refunds ?? 0);

        return $this->opening_balance + $this->cash_sales + $this->cash_in - $this->cash_out - $refunds;
    }

    public function getTotalSalesAttribute()
    {
        return $this->cash_sales + $this->card_sales + $this->bank_transfer_sales
            + $this->online_sales + $this->credit_sales;
    }

    public function getDifferenceAttribute()
    {
        if ($this->closing_balance === null) {
            return null;
        }

        return $this->closing_balance - $this->expected_cash;
    }

    /** Configured POS method: shift | day_end */
    public static function configuredMethod(): string
    {
        $method = (string) Setting::get('shift_method', self::MODE_SHIFT);

        return in_array($method, [self::MODE_SHIFT, self::MODE_DAY_END], true)
            ? $method
            : self::MODE_SHIFT;
    }

    public static function usesDayEndMethod(): bool
    {
        return self::configuredMethod() === self::MODE_DAY_END;
    }

    /** Evening cutoff (HH:MM) after which closing the last shift also ends the day. Default 22:00. */
    public static function dayEndCutoff(): string
    {
        $raw = trim((string) Setting::get('business_day_cutoff', '22:00'));
        if (! preg_match('/^\d{1,2}:\d{2}$/', $raw)) {
            return '22:00';
        }
        [$h, $m] = array_map('intval', explode(':', $raw));
        if ($h < 0 || $h > 23 || $m < 0 || $m > 59) {
            return '22:00';
        }

        return sprintf('%02d:%02d', $h, $m);
    }

    /** Local “now” for cutoff / business date (restaurant clock). */
    public static function businessNow(?\Carbon\CarbonInterface $at = null): \Carbon\Carbon
    {
        $tz = Setting::timezone();

        return $at
            ? \Carbon\Carbon::parse($at)->timezone($tz)
            : now($tz);
    }

    public static function isPastDayEndCutoff(?\Carbon\CarbonInterface $at = null): bool
    {
        $at = self::businessNow($at);
        // After midnight until 06:00 still counts as the late close / day-end window
        if ((int) $at->format('G') < 6) {
            return true;
        }

        return $at->format('H:i') >= self::dayEndCutoff();
    }

    /**
     * Business date for opening a register.
     * After midnight but before 06:00, keep previous calendar day so late closes stay on the service day.
     */
    public static function resolveBusinessDate(?\Carbon\CarbonInterface $at = null): string
    {
        $at = self::businessNow($at);
        if ((int) $at->format('G') < 6) {
            return $at->copy()->subDay()->toDateString();
        }

        return $at->toDateString();
    }

    /**
     * Start of the current service day (06:00 on the resolved business date).
     * Ready tickets from before this are treated as previous-day leftovers.
     */
    public static function businessDayStart(?\Carbon\CarbonInterface $at = null): \Carbon\Carbon
    {
        $now = self::businessNow($at);
        $date = self::resolveBusinessDate($now);

        return \Carbon\Carbon::parse($date, $now->getTimezone())->setTime(6, 0, 0);
    }

    /** Closed shifts for a business date (all cashiers). */
    public static function shiftsForDate(string $date, bool $closedOnly = true)
    {
        $q = self::with('user')
            ->where('mode', self::MODE_SHIFT)
            ->whereDate('business_date', $date)
            ->orderBy('opened_at');

        if ($closedOnly) {
            $q->whereNotNull('closed_at');
        }

        return $q->get();
    }

    public static function openShiftCountForDate(string $date): int
    {
        return self::where('mode', self::MODE_SHIFT)
            ->whereDate('business_date', $date)
            ->whereNull('closed_at')
            ->count();
    }

    public static function dayEndForDate(string $date): ?self
    {
        return self::where('mode', self::MODE_DAY_END)
            ->whereDate('business_date', $date)
            ->whereNotNull('closed_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Build a closed day_end row from all closed shifts on $date.
     */
    public static function aggregateDayEnd(
        string $date,
        float $closingCash,
        ?float $openingCash = null,
        ?int $userId = null,
        ?string $notes = null,
    ): self {
        $shifts = self::shiftsForDate($date, true);
        if ($shifts->isEmpty()) {
            throw new \RuntimeException('No closed shifts found for this date');
        }

        $opening = $openingCash ?? (float) $shifts->first()->opening_balance;

        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'mode' => self::MODE_DAY_END,
            'business_date' => $date,
            'opening_balance' => $opening,
            'closing_balance' => $closingCash,
            'cash_sales' => $shifts->sum('cash_sales'),
            'card_sales' => $shifts->sum('card_sales'),
            'bank_transfer_sales' => $shifts->sum('bank_transfer_sales'),
            'online_sales' => $shifts->sum('online_sales'),
            'credit_sales' => $shifts->sum('credit_sales'),
            'cash_in' => $shifts->sum('cash_in'),
            'cash_out' => $shifts->sum('cash_out'),
            'cash_refunds' => $shifts->sum(fn ($s) => (float) ($s->cash_refunds ?? 0)),
            'orders_count' => $shifts->sum('orders_count'),
            'notes' => trim(($notes ?? '')."\nAggregated from ".$shifts->count().' shift(s)'),
            'opened_at' => $shifts->min('opened_at'),
            'closed_at' => now(),
        ]);
    }

    /** UI labels for shift vs day-end mode. */
    public static function labels(?string $method = null): array
    {
        $method ??= self::configuredMethod();

        if ($method === self::MODE_DAY_END) {
            return [
                'open' => 'Start Day',
                'close' => 'End Day',
                'report' => 'Day Report',
                'opening' => 'Opening cash',
                'closing' => 'Closing cash',
                'session' => 'Day',
            ];
        }

        return [
            'open' => 'Open Shift',
            'close' => 'Close Shift',
            'report' => 'Shift Report',
            'opening' => 'Opening cash',
            'closing' => 'Closing cash',
            'session' => 'Shift',
        ];
    }

    /** Open session for POS (day-wide or per-cashier). */
    public static function getActiveForPos(?int $userId = null): ?self
    {
        if (self::usesDayEndMethod()) {
            return self::query()
                ->where('mode', self::MODE_DAY_END)
                ->whereNull('closed_at')
                ->orderByDesc('id')
                ->first();
        }

        if (! $userId) {
            return null;
        }

        return self::getCurrentForUser($userId);
    }

    public static function getCurrentForUser($userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('mode', self::MODE_SHIFT)
            ->whereNull('closed_at')
            ->first();
    }

    public static function hasOpenRegister($userId): bool
    {
        return self::getActiveForPos($userId) !== null;
    }

    /** Other cashiers' closed shifts on the same business date (for day-end totals). */
    public function siblingShifts()
    {
        $date = $this->business_date?->toDateString() ?? $this->opened_at?->toDateString();
        if (! $date) {
            return collect();
        }

        return self::with('user')
            ->where('mode', self::MODE_SHIFT)
            ->whereDate('business_date', $date)
            ->where('id', '!=', $this->id)
            ->orderBy('opened_at')
            ->get();
    }

    /** Cashier sales breakdown for this register session. */
    public function cashierBreakdown()
    {
        $closedAt = $this->closed_at ?? now();

        return Order::query()
            ->select(
                'users.name as cashier_name',
                'orders.cashier_id',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(orders.total_amount) as total_sales')
            )
            ->leftJoin('users', 'users.id', '=', 'orders.cashier_id')
            ->where(function ($q) use ($closedAt) {
                $q->where('orders.register_id', $this->id)
                    ->orWhere(function ($q2) use ($closedAt) {
                        $q2->whereBetween('orders.created_at', [$this->opened_at, $closedAt]);
                        if ($this->mode === self::MODE_SHIFT) {
                            $q2->where('orders.cashier_id', $this->user_id);
                        }
                    });
            })
            ->where(function ($q) {
                $q->whereNull('orders.is_void')->orWhere('orders.is_void', false);
            })
            ->whereNotIn('orders.status', ['cancelled'])
            ->groupBy('orders.cashier_id', 'users.name')
            ->orderByDesc('total_sales')
            ->get();
    }
}
