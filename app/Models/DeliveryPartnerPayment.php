<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DeliveryPartnerPayment extends Model
{
    protected $fillable = [
        'delivery_partner_id',
        'account_id',
        'amount',
        'method',
        'payment_date',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DeliveryPartnerPaymentOrder::class);
    }

    public function accountTransactions(): MorphMany
    {
        return $this->morphMany(AccountTransaction::class, 'source');
    }
}
