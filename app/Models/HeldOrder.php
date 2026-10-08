<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeldOrder extends Model
{
    use HasFactory;

    protected $table = 'held_orders';

    protected $fillable = [
        'hold_reference', 'branch_id', 'table_id', 'customer_id',
        'user_id', 'order_type', 'items', 'subtotal', 'tax_amount',
        'service_charge', 'discount_amount', 'total_amount', 'notes',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
