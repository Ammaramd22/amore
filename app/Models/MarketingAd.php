<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketingAd extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'is_active',
        'sort_order',
        'show_on_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'show_on_date' => 'date',
    ];

    /** Public URL with cache-bust query. */
    public function imageUrl(): ?string
    {
        $relative = ltrim((string) $this->image_path, '/');
        if ($relative === '' || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        $url = '/storage/'.$relative;
        try {
            return $url.'?v='.Storage::disk('public')->lastModified($relative);
        } catch (\Throwable) {
            return $url;
        }
    }

    /**
     * Ads for the customer display today (scheduled for today + everyday), capped.
     *
     * @return list<string> image URLs
     */
    public static function todayPosterUrls(int $limit = 3): array
    {
        $today = now()->toDateString();

        $ads = static::query()
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('show_on_date')
                    ->orWhereDate('show_on_date', $today);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Prefer date-specific ads first, then everyday fillers
        $dated = $ads->filter(fn (self $a) => $a->show_on_date !== null)->values();
        $everyday = $ads->filter(fn (self $a) => $a->show_on_date === null)->values();

        $picked = $dated->concat($everyday)->unique('id')->take(max(1, $limit));

        return $picked
            ->map(fn (self $a) => $a->imageUrl())
            ->filter()
            ->values()
            ->all();
    }
}
