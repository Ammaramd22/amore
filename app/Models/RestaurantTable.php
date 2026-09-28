<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tables';

    protected $fillable = [
        'floor_id', 'name', 'number', 'qr_code', 'capacity', 'status',
        'shape', 'position_x', 'position_y', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (RestaurantTable $table) {
            if (empty($table->qr_code)) {
                $table->qr_code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('qr_code', $code)->exists());

        return $code;
    }

    public function regenerateQrCode(): string
    {
        $this->qr_code = static::generateUniqueCode();
        $this->save();

        return $this->qr_code;
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function activeOrder()
    {
        return $this->hasOne(Order::class, 'table_id')
            ->openBill()
            ->whereIn('status', ['pending', 'preparing', 'ready', 'served', 'confirmed'])
            ->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    /** Occupancy follows unpaid open bills, not leftover kitchen ticket status. */
    public static function syncOccupancy(?int $tableId): void
    {
        if (! $tableId) {
            return;
        }

        $occupied = Order::findOpenBillForTable($tableId) !== null;
        static::where('id', $tableId)->update([
            'status' => $occupied ? 'occupied' : 'available',
        ]);
    }
}
