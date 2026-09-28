<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'is_custom_item', 'product_name', 'quantity', 'unit_price', 'tax_amount',
        'discount_amount', 'total_price', 'special_instructions',
        'status', 'routed_to', 'is_void', 'void_reason',
        'is_comp', 'comp_reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_void' => 'boolean',
        'is_custom_item' => 'boolean',
        'is_comp' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function addons()
    {
        return $this->hasMany(OrderItemAddon::class);
    }

    public function kitchenOrderItems()
    {
        return $this->hasMany(KitchenOrderItem::class);
    }
}
