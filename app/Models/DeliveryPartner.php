<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'commission_rate',
        'commission_type',
        'settlement_cycle',
        'collection_type',
        'tracks_settlement',
        'is_active',
        'api_config',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'tracks_settlement' => 'boolean',
        'api_config' => 'array',
    ];

    /** Partner remits later (Uber etc.) — no money at POS. */
    public function settlesLater(): bool
    {
        return ($this->collection_type ?? 'partner') === 'partner';
    }

    /** Own / restaurant delivery — pay now or COD. */
    public function collectsAtRestaurant(): bool
    {
        return ($this->collection_type ?? 'partner') === 'own';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function productPrices()
    {
        return $this->hasMany(DeliveryPartnerProductPrice::class);
    }

    public function payments()
    {
        return $this->hasMany(DeliveryPartnerPayment::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(DeliveryPartnerLedgerEntry::class);
    }

    public function calculateCommission($orderTotal)
    {
        if ($this->commission_type === 'percentage') {
            return round($orderTotal * ($this->commission_rate / 100), 2);
        }

        return (float) $this->commission_rate;
    }

    public static function active()
    {
        return self::where('is_active', true);
    }
}
