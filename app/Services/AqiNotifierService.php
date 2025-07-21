<?php

namespace App\Services;

use App\Models\AqiReading;
use App\Models\AqiSetting;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AqiNotifierService
{
    protected AqiService $aqiService;
    protected WhatsAppNotificationService $whatsAppService;

    public function __construct(
        AqiService $aqiService,
        WhatsAppNotificationService $whatsAppService
    ) {
        $this->aqiService = $aqiService;
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Check AQI and send notifications if needed
     */
    public function checkAndNotify(string $city = null, array $recipients = null, bool $force = false): array
    {
        Log::info('Starting AQI check and notification process');
        
        // Get current AQI data
        $aqiData = $this->aqiService->getAqiData($city);
        
        if (!$aqiData) {
            return [
                'success' => false,
                'message' => 'Failed to fetch AQI data',
                'aqi_data' => null,
                'notifications_sent' => []
            ];
        }

        // Store AQI reading
        $reading = $this->storeAqiReading($aqiData);
        
        // Check if notifications should be sent
        $shouldNotify = $force || $this->shouldSendNotification($aqiData);
        
        if (!$shouldNotify) {
            Log::info('No notification needed based on current AQI level and settings', [
                'aqi' => $aqiData['aqi'],
                'level' => $aqiData['level']
            ]);
            
            return [
                'success' => true,
                'message' => 'AQI checked, no notification needed',
                'aqi_data' => $aqiData,
                'aqi_reading_id' => $reading?->id,
                'notifications_sent' => []
            ];
        }

        // Get recipients list
        $recipients = $recipients ?? $this->getNotificationRecipients();
        
        if (empty($recipients)) {
            return [
                'success' => false,
                'message' => 'No recipients configured',
                'aqi_data' => $aqiData,
                'notifications_sent' => []
            ];
        }

        // Send notifications
        $notificationResults = $this->sendNotifications($aqiData, $recipients);
        
        return [
            'success' => true,
            'message' => 'AQI checked and notifications processed',
            'aqi_data' => $aqiData,
            'aqi_reading_id' => $reading?->id,
            'notifications_sent' => $notificationResults
        ];
    }

    /**
     * Store AQI reading in database
     */
    protected function storeAqiReading(array $aqiData): ?AqiReading
    {
        try {
            return AqiReading::create([
                'city' => $aqiData['city'],
                'aqi_value' => $aqiData['aqi'],
                'dominant_pollutant' => $aqiData['dominant_pollutant'],
                'pm25' => $aqiData['pollutants']['pm25'] ?? null,
                'pm10' => $aqiData['pollutants']['pm10'] ?? null,
                'o3' => $aqiData['pollutants']['o3'] ?? null,
                'no2' => $aqiData['pollutants']['no2'] ?? null,
                'so2' => $aqiData['pollutants']['so2'] ?? null,
                'co' => $aqiData['pollutants']['co'] ?? null,
                'temperature' => $aqiData['weather']['temperature'] ?? null,
                'humidity' => $aqiData['weather']['humidity'] ?? null,
                'pressure' => $aqiData['weather']['pressure'] ?? null,
                'wind_speed' => $aqiData['weather']['wind_speed'] ?? null,
                'latitude' => $aqiData['coordinates']['lat'],
                'longitude' => $aqiData['coordinates']['lng'],
                'reading_time' => $aqiData['time'] ?? now(),
                'raw_response' => $aqiData['raw_data']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to store AQI reading', [
                'error' => $e->getMessage(),
                'aqi_data' => $aqiData
            ]);
            return null;
        }
    }

    /**
     * Determine if notification should be sent
     */
    protected function shouldSendNotification(array $aqiData): bool
    {
        // Check if we're in quiet hours
        if ($this->isQuietHours()) {
            Log::info('In quiet hours, skipping notification');
            return false;
        }

        // Check if AQI level requires notification (using stored settings, not config defaults)
        if (!$this->shouldNotifyForLevel($aqiData['level'])) {
            return false;
        }

        // Check rate limiting
        if ($this->isRateLimited($aqiData['level'], $aqiData['city'])) {
            Log::info('Rate limited, skipping notification', [
                'level' => $aqiData['level'],
                'city' => $aqiData['city']
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if notifications should be sent for this AQI level (using stored settings)
     */
    protected function shouldNotifyForLevel(string $level): bool
    {
        $thresholds = $this->getSetting('thresholds', config('aqi.thresholds'));
        return $thresholds[$level]['notify'] ?? false;
    }

    /**
     * Check if we're in quiet hours
     */
    protected function isQuietHours(): bool
    {
        $quietHoursConfig = $this->getSetting('quiet_hours', config('aqi.notifications.quiet_hours'));
        
        if (!($quietHoursConfig['enabled'] ?? false)) {
            return false;
        }

        $timezone = $quietHoursConfig['timezone'] ?? config('app.timezone');
        $now = Carbon::now($timezone);
        
        $startTime = Carbon::createFromFormat('H:i', $quietHoursConfig['start'], $timezone);
        $endTime = Carbon::createFromFormat('H:i', $quietHoursConfig['end'], $timezone);
        
        // Handle overnight quiet hours (e.g., 22:00 to 07:00)
        if ($startTime > $endTime) {
            return $now >= $startTime || $now <= $endTime;
        }
        
        return $now >= $startTime && $now <= $endTime;
    }

    /**
     * Check if notifications are rate limited
     */
    protected function isRateLimited(string $level, string $city): bool
    {
        $rateLimitMinutes = $this->getSetting('rate_limit_minutes', config('aqi.notifications.rate_limit_minutes', 60));
        
        $recentNotification = NotificationLog::where('aqi_level', $level)
            ->where('city', $city)
            ->where('sent_at', '>', now()->subMinutes($rateLimitMinutes))
            ->exists();
            
        return $recentNotification;
    }

    /**
     * Get notification recipients
     */
    protected function getNotificationRecipients(): array
    {
        $recipients = $this->getSetting('recipients', []);
        
        if (empty($recipients)) {
            $defaultRecipient = config('aqi.notifications.default_recipient');
            if ($defaultRecipient) {
                $recipients = [$defaultRecipient];
            }
        }
        
        return $recipients;
    }

    /**
     * Send notifications to all recipients
     */
    protected function sendNotifications(array $aqiData, array $recipients): array
    {
        $results = [];
        $message = $this->aqiService->getAqiMessage($aqiData);
        
        foreach ($recipients as $recipient) {
            $result = $this->sendNotificationToRecipient($recipient, $message, $aqiData);
            $results[] = $result;
        }
        
        return $results;
    }

    /**
     * Send notification to a single recipient
     */
    protected function sendNotificationToRecipient(string $recipient, string $message, array $aqiData): array
    {
        $result = $this->whatsAppService->sendMessage($recipient, $message);
        
        // Log the notification attempt
        $this->logNotification([
            'recipient' => $recipient,
            'city' => $aqiData['city'],
            'aqi_value' => $aqiData['aqi'],
            'aqi_level' => $aqiData['level'],
            'message_content' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'response_data' => $result,
            'sent_at' => now()
        ]);
        
        return [
            'recipient' => $recipient,
            'success' => $result['success'],
            'message_id' => $result['message_id'] ?? null,
            'error' => $result['error'] ?? null
        ];
    }

    /**
     * Log notification attempt
     */
    protected function logNotification(array $data): void
    {
        try {
            NotificationLog::create($data);
        } catch (\Exception $e) {
            Log::error('Failed to log notification', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Get setting value with fallback
     */
    protected function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = AqiSetting::where('key', $key)->first();
        
        if ($setting) {
            return $setting->value;
        }
        
        return $default;
    }

    /**
     * Update setting value
     */
    public function updateSetting(string $key, mixed $value, string $description = null): bool
    {
        try {
            AqiSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'description' => $description
                ]
            );
            
            Log::info("Setting updated: {$key}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to update setting: {$key}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get recent notifications for display
     */
    public function getRecentNotifications(int $limit = 50): array
    {
        return NotificationLog::with([])
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get AQI statistics
     */
    public function getAqiStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        $readings = AqiReading::where('reading_time', '>=', $startDate)
            ->orderBy('reading_time')
            ->get();
            
        if ($readings->isEmpty()) {
            return [
                'count' => 0,
                'average_aqi' => null,
                'max_aqi' => null,
                'min_aqi' => null,
                'readings' => []
            ];
        }
        
        return [
            'count' => $readings->count(),
            'average_aqi' => round($readings->avg('aqi_value'), 1),
            'max_aqi' => $readings->max('aqi_value'),
            'min_aqi' => $readings->min('aqi_value'),
            'readings' => $readings->toArray()
        ];
    }

    /**
     * Test notification system
     */
    public function sendTestNotification(string $recipient): array
    {
        return $this->whatsAppService->sendTestMessage($recipient);
    }
}