<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BilliardBooking extends Model
{
    use SoftDeletes;

    public const STATUSES = ['booked', 'active', 'completed', 'cancelled', 'no_show'];

    public const SOURCES = ['walk_in', 'phone', 'online', 'admin'];

    public const PAYMENT_STATUSES = ['unpaid', 'partial', 'paid'];

    protected $fillable = [
        'booking_number',
        'billiard_table_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'source',
        'status',
        'payment_status',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'hours',
        'hourly_rate',
        'amount',
        'paid_amount',
        'notes',
        'reminder_sent_at',
        'end_alert_sent_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'reminder_sent_at' => 'datetime',
        'end_alert_sent_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(BilliardTable::class, 'billiard_table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BilliardPayment::class);
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }

    public function displayName(): string
    {
        return $this->customer_name
            ?: $this->customer?->name
            ?: 'Walk-in';
    }

    public function displayPhone(): ?string
    {
        return $this->customer_phone ?: $this->customer?->phone;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'booked' => 'Booked',
            'active' => 'Playing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'booked' => 'info',
            'active' => 'success',
            'completed' => 'secondary',
            'cancelled' => 'dark',
            'no_show' => 'warning',
            default => 'secondary',
        };
    }

    public function paymentBadgeClass(): string
    {
        return match ($this->payment_status) {
            'paid' => 'success',
            'partial' => 'warning',
            default => 'danger',
        };
    }

    public function minutesRemaining(): ?int
    {
        if (! in_array($this->status, ['booked', 'active'], true)) {
            return null;
        }
        $end = $this->status === 'active' && $this->actual_end
            ? $this->actual_end
            : $this->scheduled_end;

        return (int) now()->diffInMinutes($end, false);
    }

    public function isEndingSoon(int $withinMinutes = 10): bool
    {
        $mins = $this->minutesRemaining();

        return $mins !== null && $mins >= 0 && $mins <= $withinMinutes;
    }

    public function refreshPaymentStatus(): void
    {
        $paid = round((float) $this->payments()->sum('amount'), 2);
        $total = round((float) $this->amount, 2);
        $status = 'unpaid';
        if ($paid <= 0) {
            $status = 'unpaid';
        } elseif ($paid + 0.009 >= $total) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }
        $this->forceFill([
            'paid_amount' => $paid,
            'payment_status' => $status,
        ])->save();
    }

    public static function moduleEnabled(): bool
    {
        return (bool) Setting::get('billiards_enabled', false);
    }
}
