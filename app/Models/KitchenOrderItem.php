<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenOrderItem extends Model
{
    use HasFactory;

    protected $table = 'kitchen_order_items';

    protected $fillable = [
        'kitchen_order_id', 'order_item_id', 'product_name',
        'quantity', 'special_instructions', 'status',
        'prep_time_seconds', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function kitchenOrder()
    {
        return $this->belongsTo(KitchenOrder::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
