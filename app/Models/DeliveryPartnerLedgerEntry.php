<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPartnerLedgerEntry extends Model
{
    protected $fillable = [
        'delivery_partner_id',
        'type',
        'order_id',
        'delivery_partner_payment_id',
        'debit',
        'credit',
        'balance_after',
        'note',
        'created_by',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartnerPayment::class, 'delivery_partner_payment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
