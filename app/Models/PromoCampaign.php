<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCampaign extends Model
{
    protected $fillable = [
        'title',
        'message',
        'channels',
        'audience',
        'customer_ids',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'created_by',
        'sent_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'customer_ids' => 'array',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usesWhatsApp(): bool
    {
        return in_array('whatsapp', $this->channels ?? [], true);
    }

    public function usesSms(): bool
    {
        return in_array('sms', $this->channels ?? [], true);
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'all' => 'All with phone',
            'selected' => 'Selected',
            'loyalty' => 'Loyalty members',
            'not_loyalty' => 'Not on loyalty',
            default => (string) $this->audience,
        };
    }
}
