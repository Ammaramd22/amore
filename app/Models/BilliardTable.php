<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BilliardTable extends Model
{
    public const TYPES = ['pool', 'snooker'];

    protected $fillable = [
        'name',
        'type',
        'hourly_rate',
        'sort_order',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(BilliardBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'snooker' => 'Snooker',
            default => 'Pool',
        };
    }

    public function isAvailableBetween(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end, ?int $ignoreBookingId = null): bool
    {
        $q = $this->bookings()
            ->whereNotIn('status', ['cancelled', 'no_show', 'completed'])
            ->where('scheduled_start', '<', $end)
            ->where('scheduled_end', '>', $start);

        if ($ignoreBookingId) {
            $q->where('id', '!=', $ignoreBookingId);
        }

        return ! $q->exists();
    }
}
