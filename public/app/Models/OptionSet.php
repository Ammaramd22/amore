<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'type',
        'require_selection',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'require_selection' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function options()
    {
        return $this->hasMany(Option::class)->orderBy('display_order')->orderBy('name');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'option_set_product')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function displayLabel(): string
    {
        $label = trim((string) ($this->display_name ?: $this->name));

        return $label !== '' ? $label : (string) $this->name;
    }

    /** Products currently using this option set (Amore has no option→variant matrix). */
    public function productUsageCount(): int
    {
        return (int) $this->products()->count();
    }
}
