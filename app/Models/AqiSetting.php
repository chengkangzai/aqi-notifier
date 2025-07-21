<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AqiSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    protected $casts = [
        'value' => 'json'
    ];

    /**
     * Get setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting?->value ?? $default;
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, mixed $value, string $description = null): bool
    {
        try {
            static::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'description' => $description
                ]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
