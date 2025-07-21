<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'recipient',
        'city',
        'aqi_value',
        'aqi_level',
        'message_content',
        'status',
        'response_data',
        'sent_at'
    ];

    protected $casts = [
        'response_data' => 'json',
        'sent_at' => 'datetime',
        'aqi_value' => 'integer'
    ];

    /**
     * Get recent notifications for a recipient
     */
    public function scopeForRecipient($query, string $recipient)
    {
        return $query->where('recipient', $recipient);
    }

    /**
     * Get notifications by AQI level
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('aqi_level', $level);
    }

    /**
     * Get successful notifications
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Get failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
