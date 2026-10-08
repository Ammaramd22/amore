<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'display_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class, 'floor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
