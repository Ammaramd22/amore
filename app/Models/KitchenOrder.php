<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class KitchenOrder extends Model
{
    use HasFactory;

    protected $table = 'kitchen_orders';

    protected $fillable = [
        'order_id', 'kitchen_id', 'kot_number', 'type', 'is_reorder', 'status',
        'printed_by', 'printed_at', 'started_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'is_reorder' => 'boolean',
        'printed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Mark leftover Ready tickets from previous service day as Served
     * so POS / waiter notifications clear overnight (end of day).
     */
    public static function clearPreviousDayReady(): int
    {
        $start = CashRegister::businessDayStart();

        return self::markReadyAsServed(
            self::query()
                ->where('status', 'ready')
                ->where(function ($q) use ($start) {
                    $q->where(function ($q2) use ($start) {
                        $q2->whereNotNull('completed_at')->where('completed_at', '<', $start);
                    })->orWhere(function ($q2) use ($start) {
                        $q2->whereNull('completed_at')->where('created_at', '<', $start);
                    });
                })
                ->pluck('id')
        );
    }

    /** Mark every Ready ticket Served (used when day end is closed). */
    public static function clearAllReady(): int
    {
        return self::markReadyAsServed(
            self::query()->where('status', 'ready')->pluck('id')
        );
    }

    protected static function markReadyAsServed($ids): int
    {
        $ids = collect($ids)->filter()->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($ids) {
            self::whereIn('id', $ids)->update(['status' => 'served']);
            KitchenOrderItem::whereIn('kitchen_order_id', $ids)->update([
                'status' => 'served',
                'completed_at' => now(),
            ]);

            return $ids->count();
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function kitchen()
    {
        return $this->belongsTo(Kitchen::class);
    }

    public function items()
    {
        return $this->hasMany(KitchenOrderItem::class);
    }

    public function printer()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function scopeKitchen($query)
    {
        return $query->where('type', 'kitchen');
    }

    public function scopeBar($query)
    {
        return $query->where('type', 'bar');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Tickets that belong on live KDS / bar / customer-display boards.
     * Pay-Now takeaway stays visible (order status is still pending/preparing).
     * Settled or voided bills drop off even if kitchen never tapped Ready.
     */
    public function scopeOnLiveBoard($query)
    {
        return $query->whereHas('order', function ($q) {
            $q->where('is_void', false)
                ->whereNotIn('status', ['cancelled', 'completed', 'refunded']);
        });
    }

    public function getPrepTimeAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        return $this->started_at->diffInSeconds($this->completed_at);
    }
}
