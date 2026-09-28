<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    public const STATUSES = ['pending', 'cleared', 'returned', 'cancelled'];

    protected $fillable = [
        'supplier_id',
        'purchase_id',
        'supplier_payment_id',
        'account_id',
        'cheque_number',
        'bank_name',
        'branch_name',
        'amount',
        'cheque_date',
        'due_date',
        'status',
        'returned_reason',
        'cleared_at',
        'returned_at',
        'last_reminded_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cheque_date' => 'date',
        'due_date' => 'date',
        'cleared_at' => 'datetime',
        'returned_at' => 'datetime',
        'last_reminded_at' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->pending()
            ->whereDate('due_date', '<=', now()->addDays(max(0, $days))->toDateString());
    }

    public function scopeOverdue($query)
    {
        return $query->pending()->whereDate('due_date', '<', now()->toDateString());
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'cleared' => 'Cleared',
            'returned' => 'Returned',
            'cancelled' => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'cleared' => 'success',
            'returned' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public static function managementEnabled(): bool
    {
        return (bool) Setting::get('cheque_management_enabled', false);
    }
}
