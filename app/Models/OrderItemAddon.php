<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemAddon extends Model
{
    use HasFactory;

    protected $table = 'order_item_addons';

    protected $fillable = [
        'order_item_id', 'product_addon_id', 'addon_id', 'addon_name', 'price', 'hide_on_receipt',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'hide_on_receipt' => 'boolean',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function addon()
    {
        return $this->belongsTo(ProductAddon::class, 'product_addon_id');
    }

    public function sharedAddon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
