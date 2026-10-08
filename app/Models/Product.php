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

    /** Modifier / add-on sets assigned to this product. */
    public function addonGroups()
    {
        return $this->belongsToMany(AddonGroup::class, 'addon_group_product')->withTimestamps();
    }

    /** Preference option sets (Milk Type, Sugar Level, etc.) — not priced modifiers. */
    public function optionSets()
    {
        return $this->belongsToMany(OptionSet::class, 'option_set_product')->withTimestamps();
    }

    /**
     * Active option sets with active options for POS.
     */
    public function posOptionSets()
    {
        $sets = $this->relationLoaded('optionSets')
            ? $this->optionSets->where('is_active', true)->sortBy([['display_order', 'asc'], ['name', 'asc']])->values()
            : $this->optionSets()->active()->ordered()->with(['options' => fn ($q) => $q->active()->ordered()])->get();

        return $sets->map(function ($set) {
            $options = $set->relationLoaded('options')
                ? $set->options->where('is_active', true)->values()
                : $set->options()->active()->ordered()->get();

            return [
                'id' => $set->id,
                'name' => $set->name,
                'display_name' => $set->displayLabel(),
                'type' => $set->type ?: 'text',
                'require_selection' => (bool) $set->require_selection,
                'options' => $options,
            ];
        })->filter(fn ($set) => $set['options']->isNotEmpty())->values();
    }

    /**
     * Active modifiers for POS: direct shared add-ons + add-ons from assigned sets.
     * Falls back to legacy per-product rows when neither is present.
     */
    public function posAddons()
    {
        $direct = $this->relationLoaded('sharedAddons')
            ? $this->sharedAddons->where('is_active', true)->sortBy([['display_order', 'asc'], ['name', 'asc']])->values()
            : $this->sharedAddons()->active()->ordered()->get();

        $fromGroups = collect();
        if ($this->relationLoaded('addonGroups')) {
            $fromGroups = $this->addonGroups
                ->filter(fn ($g) => (bool) ($g->is_active ?? true))
                ->flatMap(function ($group) {
                    if ($group->relationLoaded('addons')) {
                        return $group->addons->filter(function ($addon) {
                            return (bool) ($addon->is_active ?? true) && (bool) ($addon->pivot->is_available ?? true);
                        });
                    }

                    return $group->addons()->where('addons.is_active', true)->get();
                });
        } else {
            $fromGroups = $this->addonGroups()
                ->where('addon_groups.is_active', true)
                ->with(['addons' => fn ($q) => $q->where('addons.is_active', true)->ordered()])
                ->get()
                ->flatMap(fn ($group) => $group->addons);
        }

        $merged = $direct->concat($fromGroups)->unique('id')->values();
        if ($merged->isNotEmpty()) {
            return $merged;
        }

        if ($this->relationLoaded('addons')) {
            return $this->addons->where('is_active', true)->sortBy('name')->values();
        }

        return $this->addons()->active()->orderBy('name')->get();
    }

    /** Active modifier sets with available modifiers (for grouped POS UI). */
    public function posModifierSets(?int $branchId = null)
    {
        // Product-assigned sets always show in POS. Assignment is the source of truth.
        $query = $this->relationLoaded('addonGroups')
            ? null
            : $this->addonGroups()->active()->ordered()->forBranch($branchId)
                ->with(['addons' => fn ($q) => $q->orderBy('addons.name'), 'branches:id']);

        $groups = $query
            ? $query->get()
            : $this->addonGroups
                ->filter(fn ($g) => (bool) ($g->is_active ?? true))
                ->sortBy([['display_order', 'asc'], ['name', 'asc']])
                ->values();

        if ($branchId && \App\Services\BranchService::enabled()) {
            $groups = $groups->filter(function ($group) use ($branchId) {
                if (! $group->relationLoaded('branches')) {
                    return true;
                }

                return $group->branches->isEmpty() || $group->branches->contains('id', $branchId);
            })->values();
        }

        return $groups->map(function ($group) {
            try {
                $addons = $group->relationLoaded('addons')
                    ? $group->addons
                    : $group->addons()->orderBy('addons.name')->get();
            } catch (\Throwable $e) {
                // Fallback if pivot/schema mismatch — raw join without optional pivot cols
                $addons = Addon::query()
                    ->join('addon_group_addon', 'addons.id', '=', 'addon_group_addon.addon_id')
                    ->where('addon_group_addon.addon_group_id', $group->id)
                    ->select('addons.*')
                    ->orderBy('addons.name')
                    ->get();
            }

            // For product-assigned sets, show linked modifiers unless pivot explicitly unavailable.
            // Do NOT drop them just because addons.is_active was toggled elsewhere.
            $addons = $addons->filter(function ($addon) {
                $available = $addon->pivot->is_available ?? true;

                return (bool) $available;
            })->values();

            // Last resort: if filter removed everything but group has rows, show all
            if ($addons->isEmpty()) {
                try {
                    $addons = $group->relationLoaded('addons')
                        ? $group->addons->values()
                        : $group->addons()->orderBy('addons.name')->get();
                } catch (\Throwable $e) {
                    $addons = collect();
                }
            }

            return [
                'id' => $group->id,
                'name' => $group->name,
                'display_name' => $group->displayLabel(),
                'selection_type' => $group->selection_type ?: 'list',
                'require_selection' => (bool) ($group->require_selection ?? false),
                'allow_multiple' => (bool) ($group->allow_multiple ?? true),
                'hide_on_receipt' => (bool) ($group->hide_on_receipt ?? false),
                'addons' => $addons,
            ];
        })->filter(fn ($set) => $set['addons']->isNotEmpty())->values();
    }

    public function refreshHasAddonsFlag(): void
    {
        // Any assigned active modifier set counts — even if child modifiers were marked inactive.
        $hasFromSets = $this->addonGroups()
            ->where('addon_groups.is_active', true)
            ->exists();

        $this->update([
            'has_addons' => $this->sharedAddons()->exists()
                || $this->addons()->exists()
                || $hasFromSets,
        ]);
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
