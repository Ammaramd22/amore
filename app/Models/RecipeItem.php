<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'ingredient_id', 'quantity', 'unit', 'cost', 'notes'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'cost' => 'decimal:4',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
