<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id', 'ingredient_id', 'product_id', 'quantity', 'unit',
        'unit_price', 'total_price', 'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function itemName(): string
    {
        return $this->product?->name ?? $this->ingredient?->name ?? 'Unknown';
    }
}
