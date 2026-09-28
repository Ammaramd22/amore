<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id',
        'description', 'properties', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'model_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForModel($query, string $type, int $id)
    {
        return $query->where('model_type', $type)->where('model_id', $id);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
