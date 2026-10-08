<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'phone', 'email', 'address',
        'date_of_birth', 'anniversary_date', 'notes',
        'loyalty_points',         'loyalty_joined', 'loyalty_token',
        'loyalty_stamps', 'loyalty_free_drinks', 'loyalty_joined_at', 'loyalty_expires_at',
        'outstanding_balance', 'allow_credit',
        'credit_limit', 'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'anniversary_date' => 'date',
        'loyalty_points' => 'integer',
        'loyalty_joined' => 'boolean',
        'loyalty_stamps' => 'integer',
        'loyalty_free_drinks' => 'integer',
        'loyalty_joined_at' => 'datetime',
        'loyalty_expires_at' => 'datetime',
        'outstanding_balance' => 'decimal:2',
        'allow_credit' => 'boolean',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function loyaltyLogs()
    {
        return $this->hasMany(LoyaltyStampLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLoyaltyJoined($query)
    {
        return $query->where('loyalty_joined', true);
    }

    public function hasBirthdayToday(): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }
        return $this->date_of_birth->format('m-d') === now()->format('m-d');
    }

    public function hasAnniversaryToday(): bool
    {
        if (!$this->anniversary_date) {
            return false;
        }
        return $this->anniversary_date->format('m-d') === now()->format('m-d');
    }
}
