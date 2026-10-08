<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_set_id',
        'name',
        'color',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function optionSet()
    {
        return $this->belongsTo(OptionSet::class);
    }

    public function orderItemOptions()
    {
        return $this->hasMany(OrderItemOption::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * Products that use this option's set and have at least one variation.
     * Amore variants are flat (not built from option values), so this is the
     * closest accurate "item variations" style usage count.
     */
    public function itemVariationUsageCount(): int
    {
        $setId = (int) $this->option_set_id;
        if ($setId <= 0) {
            return 0;
        }

        return (int) Product::query()
            ->whereHas('optionSets', fn ($q) => $q->where('option_sets.id', $setId))
            ->whereHas('variants')
            ->count();
    }

    public function isUsedInOrders(): bool
    {
        return $this->orderItemOptions()->exists();
    }
}
