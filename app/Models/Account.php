<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'payment_methods',
        'opening_balance',
        'current_balance',
        'notes',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'payment_methods' => 'array',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function handlesMethod(string $method): bool
    {
        $methods = $this->payment_methods ?? [];

        return in_array($method, $methods, true);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'cash' => 'Cash',
            'bank' => 'Bank',
            default => 'Other',
        };
    }

    public static function forPaymentMethod(string $method): ?self
    {
        return static::active()
            ->get()
            ->first(fn (self $account) => $account->handlesMethod($method));
    }

    public static function defaultCash(): ?self
    {
        return static::active()->where('type', 'cash')->orderBy('id')->first()
            ?? static::forPaymentMethod('cash');
    }

    public static function defaultBank(): ?self
    {
        return static::active()->where('type', 'bank')->orderBy('id')->first()
            ?? static::forPaymentMethod('card')
            ?? static::forPaymentMethod('bank_transfer');
    }
}
