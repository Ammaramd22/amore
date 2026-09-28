<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilliardPayment extends Model
{
    public const METHODS = ['cash', 'card', 'bank_transfer', 'online', 'credit'];

    protected $fillable = [
        'billiard_booking_id',
        'method',
        'amount',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(BilliardBooking::class, 'billiard_booking_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'cash' => 'Cash',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'online' => 'Online',
            'credit' => 'Credit',
            default => ucfirst(str_replace('_', ' ', (string) $this->method)),
        };
    }
}
