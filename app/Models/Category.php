<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'slug', 'description', 'image',
        'color', 'display_order', 'is_active', 'show_in_pos', 'show_in_qr',
        'kitchen_id', 'type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_pos' => 'boolean',
        'show_in_qr' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function kitchen()
    {
        return $this->belongsTo(Kitchen::class);
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePosVisible($query)
    {
        return $query->where('show_in_pos', true);
    }

    public function scopeQrVisible($query)
    {
        return $query->where('show_in_qr', true);
    }

    /** Relative URL so images work on any host (respos.test, localhost, production). */
    public function imageUrl(?string $fallback = null): string
    {
        $fallback = $fallback ?? '/images/product-placeholder.svg';

        $raw = trim((string) ($this->image ?? ''));
        if ($raw === '') {
            return $fallback;
        }

        $path = str_replace('\\', '/', $raw);
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return $fallback;
        }

        return '/storage/'.$path;
    }

    public function hasImageFile(): bool
    {
        return $this->imageUrl('') !== '';
    }
}
