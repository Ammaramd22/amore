<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPartnerProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'delivery_partner_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }
}
