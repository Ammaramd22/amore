<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'username',
        'login_code',
        'password',
        'pin',
        'avatar',
        'employee_code',
        'user_type',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function hasPin(): bool
    {
        return ! empty($this->pin);
    }

    public function verifyPin(string $pin): bool
    {
        if (! $this->pin) {
            return false;
        }

        return \Illuminate\Support\Facades\Hash::check($pin, $this->pin);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /** Active branches this user may work in (empty + no legacy = all when admin). */
    public function accessibleBranches()
    {
        $assigned = $this->branches()->active()->ordered()->get();
        if ($assigned->isNotEmpty()) {
            return $assigned;
        }

        if ($this->branch_id) {
            $one = Branch::query()->active()->where('id', $this->branch_id)->get();
            if ($one->isNotEmpty()) {
                return $one;
            }
        }

        return collect();
    }

    public function canAccessAllBranches(): bool
    {
        if ($this->isSoftwareOwner()) {
            return true;
        }

        // Explicit "All branches": no pivot rows and null branch_id
        return $this->branches()->count() === 0 && empty($this->branch_id);
    }

    public function defaultBranchId(): ?int
    {
        $pivotDefault = $this->branches()->wherePivot('is_default', true)->value('branches.id');
        if ($pivotDefault) {
            return (int) $pivotDefault;
        }

        return $this->branch_id ? (int) $this->branch_id : null;
    }

    public function syncBranchAccess(array $branchIds, ?int $defaultId = null): void
    {
        $branchIds = collect($branchIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $sync = [];
        foreach ($branchIds as $id) {
            $sync[$id] = ['is_default' => $defaultId ? ((int) $defaultId === $id) : false];
        }
        if ($branchIds->isNotEmpty() && ! $defaultId) {
            $first = $branchIds->first();
            $sync[$first]['is_default'] = true;
            $defaultId = $first;
        }

        $this->branches()->sync($sync);
        $this->branch_id = $defaultId; // legacy primary
        $this->save();
    }

    public function ordersAsCashier()
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }

    public function ordersAsWaiter()
    {
        return $this->hasMany(Order::class, 'waiter_id');
    }

    public function ordersAsDelivery()
    {
        return $this->hasMany(Order::class, 'delivery_staff_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('user_type', $type);
    }

    public function isWaiter(): bool
    {
        return $this->user_type === 'waiter' || $this->hasRole('waiter');
    }

    public function isKitchenStaff(): bool
    {
        return $this->user_type === 'kitchen' || $this->hasRole('kitchen');
    }

    public function isBilliardsStaff(): bool
    {
        return $this->user_type === 'billiards' || $this->hasRole('billiards');
    }

    /** Avenque / QRPOS software owner — sees system-only settings & features. */
    public function isSoftwareOwner(): bool
    {
        return $this->user_type === 'software_owner'
            || $this->hasRole('software_owner')
            || $this->can('owner.access');
    }

    /** Assignable roles for restaurant user management (never exposes software_owner). */
    public static function assignableRoleNames(): array
    {
        return ['admin', 'manager', 'cashier', 'waiter', 'kitchen', 'delivery', 'billiards'];
    }

    /** Permissions only the software owner may grant / see. */
    public static function ownerOnlyPermissionNames(): array
    {
        return [
            'settings.system',
            'settings.email',
            'settings.pwa',
            'settings.integrations',
            'owner.access',
        ];
    }
}
