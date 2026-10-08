<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddonGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'selection_type',
        'require_selection',
        'allow_multiple',
        'hide_on_receipt',
        'show_in_pos',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'require_selection' => 'boolean',
        'allow_multiple' => 'boolean',
        'hide_on_receipt' => 'boolean',
        'show_in_pos' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function addons()
    {
        // Only pivot columns that exist since 2026_10_01_140000.
        // Optional later columns (is_preselected / is_available) are read with ?? defaults.
        return $this->belongsToMany(Addon::class, 'addon_group_addon')
            ->withPivot(['display_order'])
            ->withTimestamps()
            ->orderByPivot('display_order')
            ->orderBy('addons.name');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'addon_group_product')->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'addon_group_branch')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePosVisible($query)
    {
        return $query->where('show_in_pos', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

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

    public function displayLabel(): string
    {
        $label = trim((string) ($this->display_name ?: $this->name));

        return $label !== '' ? $label : $this->name;
    }

    public function syncBranches(array $branchIds): void
    {
        $ids = collect($branchIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $this->branches()->sync($ids);
    }
}
