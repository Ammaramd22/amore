<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'total_cost', 'wastage_percentage',
        'instructions', 'yield_quantity',
    ];

    protected $casts = [
        'total_cost' => 'decimal:4',
        'wastage_percentage' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function getProfitMarginAttribute(): float
    {
        if (!$this->product || $this->product->selling_price <= 0) {
            return 0;
        }
        $cost = $this->total_cost * (1 + $this->wastage_percentage / 100);
        return (($this->product->selling_price - $cost) / $this->product->selling_price) * 100;
    }
}
