<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'subcategory_id', 'name', 'code', 'barcode',
        'description', 'image', 'cost_price', 'selling_price', 'tax_rate',
        'tax_inclusive', 'discount_amount', 'discount_type',
        'has_variants', 'has_addons', 'is_combo', 'routed_to',
        'is_available', 'show_in_pos', 'show_in_qr', 'track_stock', 'stock_quantity', 'expiry_date', 'display_order',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_inclusive' => 'boolean',
        'discount_amount' => 'decimal:2',
        'has_variants' => 'boolean',
        'has_addons' => 'boolean',
        'is_combo' => 'boolean',
        'is_available' => 'boolean',
        'show_in_pos' => 'boolean',
        'show_in_qr' => 'boolean',
        'track_stock' => 'boolean',
        'expiry_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class)->withTimestamps();
    }

    /** Visible in a branch: linked branches, or none linked (= all / legacy). */
    public function scopeForBranch($query, ?int $branchId)
    {
        if (! $branchId || ! \App\Services\BranchService::enabled()) {
            return $query;
        }

        return $query->where(function ($q) use ($branchId) {
            $q->whereDoesntHave('branches')
                ->orWhereHas('branches', fn ($b) => $b->where('branches.id', $branchId));
        });
    }

    /** For purchases: must be linked to the supplier. */
    public function scopeForSupplier($query, ?int $supplierId)
    {
        if (! $supplierId) {
            return $query;
        }

        return $query->whereHas('suppliers', fn ($s) => $s->where('suppliers.id', $supplierId));
    }

    public function syncBranches(array $branchIds): void
    {
        $ids = collect($branchIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $this->branches()->sync($ids);
    }

    public function syncSuppliers(array $supplierIds): void
    {
        $ids = collect($supplierIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $this->suppliers()->sync($ids);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** Legacy per-product add-on rows (kept for order history / migration). */
    public function addons()
    {
        return $this->hasMany(ProductAddon::class);
    }

    /** Shared add-ons catalog (many-to-many). Prefer this for POS / admin. */
    public function sharedAddons()
    {
        return $this->belongsToMany(Addon::class, 'addon_product')->withTimestamps();
    }

    /** Active add-ons for POS: shared catalog first, else legacy rows. */
    public function posAddons()
    {
        $shared = $this->sharedAddons()->active()->ordered()->get();
        if ($shared->isNotEmpty()) {
            return $shared;
        }

        return $this->addons()->active()->orderBy('name')->get();
    }

    public function partnerPrices()
    {
        return $this->hasMany(DeliveryPartnerProductPrice::class);
    }

    /** @return array<int,float> partner_id => price */
    public function partnerPriceMap(): array
    {
        return $this->partnerPrices
            ->mapWithKeys(fn ($row) => [(int) $row->delivery_partner_id => (float) $row->price])
            ->all();
    }

    public function priceForPartner(?int $partnerId): float
    {
        if ($partnerId) {
            $map = $this->relationLoaded('partnerPrices')
                ? $this->partnerPriceMap()
                : $this->partnerPrices()->pluck('price', 'delivery_partner_id')->map(fn ($p) => (float) $p)->all();
            if (isset($map[$partnerId])) {
                return (float) $map[$partnerId];
            }
        }

        return (float) $this->final_price;
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopePosVisible($query)
    {
        return $query->where('show_in_pos', true);
    }

    public function scopeQrVisible($query)
    {
        return $query->where('show_in_qr', true);
    }

    public function getFinalPriceAttribute(): float
    {
        $price = (float) $this->selling_price;
        if ($this->discount_type === 'percentage' && $this->discount_amount > 0) {
            $price -= $price * ($this->discount_amount / 100);
        } elseif ($this->discount_type === 'fixed' && $this->discount_amount > 0) {
            $price -= $this->discount_amount;
        }
        return max(0, $price);
    }

    public function getTaxAmountAttribute(): float
    {
        if ($this->tax_inclusive) {
            return $this->final_price - ($this->final_price / (1 + ($this->tax_rate / 100)));
        }
        return $this->final_price * ($this->tax_rate / 100);
    }

    public function getPriceWithTaxAttribute(): float
    {
        if ($this->tax_inclusive) {
            return $this->final_price;
        }
        return $this->final_price + $this->tax_amount;
    }

    /** Relative URL so images work on any host (respos.test, localhost, production). */
    public function imageUrl(): string
    {
        $fallback = '/images/product-placeholder.svg';

        $raw = trim((string) ($this->image ?? ''));
        if ($raw === '') {
            return $fallback;
        }

        // Normalize stored value to disk-relative path under public disk
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

        // Prefer real path check — works even when public/storage symlink is broken
        $absolute = storage_path('app/public/'.str_replace(['..', "\0"], '', $path));
        if (! is_file($absolute) && ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return $fallback;
        }

        return '/storage/'.$path;
    }

    public function hasImageFile(): bool
    {
        return $this->imageUrl() !== '/images/product-placeholder.svg';
    }
}
