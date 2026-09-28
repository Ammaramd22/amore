<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterRating extends Model
{
    public const EMOJIS = [
        1 => '😠',
        2 => '😕',
        3 => '😐',
        4 => '🙂',
        5 => '😍',
    ];

    public const LABELS = [
        1 => 'Very bad',
        2 => 'Bad',
        3 => 'OK',
        4 => 'Good',
        5 => 'Excellent',
    ];

    protected $fillable = [
        'order_id',
        'waiter_id',
        'rating',
        'emoji',
        'rated_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'rated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public static function emojiFor(int $rating): string
    {
        return self::EMOJIS[$rating] ?? '😐';
    }
}
