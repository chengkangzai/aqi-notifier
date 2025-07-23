<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WAQI API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the World Air Quality Index API
    |
    */
    'waqi' => [
        'token' => env('WAQI_API_TOKEN'),
        'base_url' => 'https://api.waqi.info',
        'default_city' => 'kuala-lumpur',
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default AQI Thresholds
    |--------------------------------------------------------------------------
    |
    | These are the default AQI thresholds for notifications.
    | Users can override these via the web interface.
    |
    */
    'thresholds' => [
        'good' => [
            'min' => 0,
            'max' => 50,
            'color' => 'green',
            'message' => 'Air quality is good. Enjoy outdoor activities!',
            'notify' => false,
        ],
        'moderate' => [
            'min' => 51,
            'max' => 100,
            'color' => 'yellow',
            'message' => 'Air quality is moderate. Sensitive people should consider limiting outdoor activities.',
            'notify' => false,
        ],
        'unhealthy_sensitive' => [
            'min' => 101,
            'max' => 150,
            'color' => 'orange',
            'message' => 'Air quality is unhealthy for sensitive groups. Children, elderly, and people with respiratory conditions should limit outdoor activities.',
            'notify' => true,
        ],
        'unhealthy' => [
            'min' => 151,
            'max' => 200,
            'color' => 'red',
            'message' => 'Air quality is unhealthy. Everyone should limit outdoor activities and consider wearing masks.',
            'notify' => true,
        ],
        'very_unhealthy' => [
            'min' => 201,
            'max' => 300,
            'color' => 'purple',
            'message' => 'Air quality is very unhealthy. Avoid outdoor activities. Stay indoors and use air purifiers if available.',
            'notify' => true,
        ],
        'hazardous' => [
            'min' => 301,
            'max' => 500,
            'color' => 'maroon',
            'message' => 'Air quality is hazardous. Emergency conditions. Avoid all outdoor activities.',
            'notify' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Default notification behavior and settings
    |
    */
    'notifications' => [
        'default_recipient' => env('AQI_DEFAULT_RECIPIENT'),
        'session_name' => env('WAHA_SESSION_NAME', 'default'),
        'timeout' => 60, // WhatsApp API timeout in seconds
        'retry_attempts' => 3, // Number of retry attempts for failed messages
        'retry_delay' => 5, // Initial delay between retries in seconds
        'retry_exponential_backoff' => true, // Use exponential backoff for retries
        'rate_limit_minutes' => 60, // Don't send same level alert more than once per hour
        'quiet_hours' => [
            'enabled' => false,
            'start' => '22:00',
            'end' => '07:00',
            'timezone' => 'Asia/Kuala_Lumpur',
        ],
        'message_template' => "🌬️ *AQI Alert for {city}*\n\n📊 Current AQI: *{aqi}*\n🎯 Level: *{level}*\n🕐 Time: {timestamp}\n\n{message}\n\n🌡️ Temperature: {temperature}°C\n💧 Humidity: {humidity}%",
    ],
];