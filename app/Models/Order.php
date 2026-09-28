<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number', 'branch_id', 'table_id', 'guest_count', 'customer_id',
        'waiter_id', 'cashier_id', 'delivery_staff_id',
        'order_type', 'source', 'status', 'approval_status', 'payment_status',
        'subtotal', 'tax_amount', 'service_charge', 'discount_amount',
        'delivery_charge', 'tip_amount', 'total_amount', 'paid_amount', 'change_amount',
        'order_notes', 'pickup_time', 'delivery_address', 'google_maps_link',
        'delivery_partner_id', 'payment_on_delivery', 'delivery_status', 'completed_at', 'bill_requested_at',
        'delivery_partner_fee', 'partner_due_amount', 'partner_settlement_status', 'partner_settled_amount',
        'merged_from_order_id', 'is_void', 'void_type', 'void_reason', 'voided_by',
        'register_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'delivery_partner_fee' => 'decimal:2',
        'partner_due_amount' => 'decimal:2',
        'partner_settled_amount' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'payment_on_delivery' => 'boolean',
        'is_void' => 'boolean',
        'completed_at' => 'datetime',
        'bill_requested_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function deliveryStaff()
    {
        return $this->belongsTo(User::class, 'delivery_staff_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function kitchenOrders()
    {
        return $this->hasMany(KitchenOrder::class);
    }

    public function waiterRating()
    {
        return $this->hasOne(WaiterRating::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeNotVoid($query)
    {
        return $query->where('is_void', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'preparing', 'ready', 'served']);
    }

    public function scopeAwaitingQrApproval($query)
    {
        return $query->where('source', 'qr')
            ->where('approval_status', 'pending')
            ->where('is_void', false)
            ->whereNotIn('status', ['cancelled', 'completed', 'refunded']);
    }

    /**
     * Unpaid dine-in bill that owns the table (excludes QR orders awaiting approval).
     */
    public function scopeOpenBill($query)
    {
        return $query->where('payment_status', 'unpaid')
            ->where('is_void', false)
            ->whereNull('completed_at')
            ->whereNotIn('status', ['cancelled', 'completed', 'refunded'])
            ->where(function ($q) {
                $q->where('source', '!=', 'qr')
                    ->orWhere('approval_status', 'approved')
                    ->orWhereNull('approval_status');
            });
    }

    /** Open unpaid bills that must be cleared before day end (same set as POS Open Bills). */
    public static function openBillCountForDayEnd(): int
    {
        return static::query()
            ->whereIn('order_type', ['dine_in', 'takeaway', 'express', 'delivery'])
            ->openBill()
            ->count();
    }

    public static function findOpenBillForTable(?int $tableId, ?int $excludeId = null): ?self
    {
        if (! $tableId) {
            return null;
        }

        $query = static::query()
            ->openBill()
            ->where('table_id', $tableId)
            ->where('order_type', 'dine_in');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->latest('id')->first();
    }

    public static function findAwaitingQrForTable(int $tableId): ?self
    {
        return static::awaitingQrApproval()
            ->where('table_id', $tableId)
            ->latest('id')
            ->first();
    }

    public function isAwaitingQrApproval(): bool
    {
        return $this->source === 'qr'
            && $this->approval_status === 'pending'
            && ! $this->is_void
            && ! in_array($this->status, ['cancelled', 'completed', 'refunded'], true);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function remainingBalance(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }
}
