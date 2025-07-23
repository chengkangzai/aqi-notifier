<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AqiReading extends Model
{
    protected $fillable = [
        'city',
        'aqi_value',
        'dominant_pollutant',
        'pm25',
        'pm10',
        'o3',
        'no2',
        'so2',
        'co',
        'temperature',
        'humidity',
        'pressure',
        'wind_speed',
        'latitude',
        'longitude',
        'reading_time',
        'raw_response'
    ];

    protected $casts = [
        'aqi_value' => 'integer',
        'pm25' => 'decimal:2',
        'pm10' => 'decimal:2',
        'o3' => 'decimal:2',
        'no2' => 'decimal:2',
        'so2' => 'decimal:2',
        'co' => 'decimal:2',
        'temperature' => 'decimal:2',
        'humidity' => 'integer',
        'pressure' => 'integer',
        'wind_speed' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'reading_time' => 'datetime',
        'raw_response' => 'json'
    ];

    /**
     * Get readings for a specific city
     */
    public function scopeForCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Get readings within AQI range
     */
    public function scopeAqiRange($query, int $min, int $max)
    {
        return $query->whereBetween('aqi_value', [$min, $max]);
    }

    /**
     * Get recent readings
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('reading_time', '>=', now()->subHours($hours));
    }

    /**
     * Get readings by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('reading_time', [$startDate, $endDate]);
    }

    /**
     * Get AQI value as 'aqi' attribute for consistency
     */
    public function getAqiAttribute(): ?int
    {
        return $this->aqi_value;
    }

    /**
     * Get AQI level for this reading
     */
    public function getAqiLevelAttribute(): string
    {
        $thresholds = config('aqi.thresholds');
        
        foreach ($thresholds as $level => $config) {
            if ($this->aqi_value >= $config['min'] && $this->aqi_value <= $config['max']) {
                return $level;
            }
        }
        
        return 'hazardous';
    }
}
