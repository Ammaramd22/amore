<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountTransaction extends Model
{
    protected $fillable = [
        'account_id',
        'direction',
        'amount',
        'balance_after',
        'payment_method',
        'description',
        'reference',
        'source_type',
        'source_id',
        'created_by',
        'transacted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function isIn(): bool
    {
        return $this->direction === 'in';
    }
}
