<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AqiService
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('aqi.waqi.base_url');
        $this->token = config('aqi.waqi.token');
        $this->timeout = config('aqi.waqi.timeout', 30);
    }

    /**
     * Fetch AQI data for a specific city
     */
    public function getAqiData(string $city = null): ?array
    {
        $city = $city ?? config('aqi.waqi.default_city');
        
        try {
            Log::info("Fetching AQI data for city: {$city}");
            
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/feed/{$city}/", [
                    'token' => $this->token
                ]);

            if (!$response->successful()) {
                Log::error("WAQI API request failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'city' => $city
                ]);
                return null;
            }

            $data = $response->json();
            
            if ($data['status'] !== 'ok') {
                Log::error("WAQI API returned error status", [
                    'status' => $data['status'] ?? 'unknown',
                    'data' => $data,
                    'city' => $city
                ]);
                return null;
            }

            Log::info("Successfully fetched AQI data for {$city}", [
                'aqi' => $data['data']['aqi'] ?? 'unknown'
            ]);

            return $this->parseAqiData($data['data']);
            
        } catch (ConnectionException $e) {
            Log::error("Connection error while fetching AQI data", [
                'message' => $e->getMessage(),
                'city' => $city
            ]);
            return null;
            
        } catch (RequestException $e) {
            Log::error("Request error while fetching AQI data", [
                'message' => $e->getMessage(),
                'city' => $city
            ]);
            return null;
            
        } catch (\Exception $e) {
            Log::error("Unexpected error while fetching AQI data", [
                'message' => $e->getMessage(),
                'city' => $city
            ]);
            return null;
        }
    }

    /**
     * Parse raw WAQI API response into structured data
     */
    protected function parseAqiData(array $data): array
    {
        return [
            'aqi' => (int) $data['aqi'],
            'city' => $data['city']['name'] ?? 'Unknown',
            'dominant_pollutant' => $data['dominentpol'] ?? null,
            'time' => $this->parseTimestamp($data['time'] ?? null),
            'coordinates' => [
                'lat' => $data['city']['geo'][0] ?? null,
                'lng' => $data['city']['geo'][1] ?? null,
            ],
            'pollutants' => $this->parsePollutants($data['iaqi'] ?? []),
            'weather' => $this->parseWeather($data['iaqi'] ?? []),
            'raw_data' => $data,
            'level' => $this->getAqiLevel((int) $data['aqi']),
        ];
    }

    /**
     * Extract pollutant data from iaqi array
     */
    protected function parsePollutants(array $iaqi): array
    {
        $pollutants = [];
        
        $pollutantKeys = ['pm25', 'pm10', 'o3', 'no2', 'so2', 'co'];
        
        foreach ($pollutantKeys as $key) {
            if (isset($iaqi[$key]['v'])) {
                $pollutants[$key] = (float) $iaqi[$key]['v'];
            }
        }
        
        return $pollutants;
    }

    /**
     * Extract weather data from iaqi array
     */
    protected function parseWeather(array $iaqi): array
    {
        $weather = [];
        
        if (isset($iaqi['t']['v'])) {
            $weather['temperature'] = (float) $iaqi['t']['v'];
        }
        
        if (isset($iaqi['h']['v'])) {
            $weather['humidity'] = (int) $iaqi['h']['v'];
        }
        
        if (isset($iaqi['p']['v'])) {
            $weather['pressure'] = (int) $iaqi['p']['v'];
        }
        
        if (isset($iaqi['w']['v'])) {
            $weather['wind_speed'] = (float) $iaqi['w']['v'];
        }
        
        return $weather;
    }

    /**
     * Parse API timestamp format
     */
    protected function parseTimestamp(?array $timeData): ?Carbon
    {
        if (!$timeData || !isset($timeData['s'])) {
            return null;
        }
        
        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $timeData['s']);
        } catch (\Exception $e) {
            Log::warning("Failed to parse timestamp", [
                'timestamp' => $timeData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Determine AQI level based on value
     */
    public function getAqiLevel(int $aqi): string
    {
        $thresholds = config('aqi.thresholds');
        
        foreach ($thresholds as $level => $config) {
            if ($aqi >= $config['min'] && $aqi <= $config['max']) {
                return $level;
            }
        }
        
        // If AQI is above all thresholds, return the highest level
        return 'hazardous';
    }

    /**
     * Get AQI level configuration
     */
    public function getAqiLevelConfig(string $level): ?array
    {
        return config("aqi.thresholds.{$level}");
    }

    /**
     * Check if AQI level should trigger notification
     */
    public function shouldNotify(int $aqi): bool
    {
        $level = $this->getAqiLevel($aqi);
        $config = $this->getAqiLevelConfig($level);
        
        return $config['notify'] ?? false;
    }

    /**
     * Get formatted message for AQI level
     */
    public function getAqiMessage(array $aqiData): string
    {
        $level = $aqiData['level'];
        $config = $this->getAqiLevelConfig($level);
        $template = config('aqi.notifications.message_template');
        
        return strtr($template, [
            '{city}' => $aqiData['city'],
            '{aqi}' => $aqiData['aqi'],
            '{level}' => ucfirst(str_replace('_', ' ', $level)),
            '{timestamp}' => $aqiData['time']?->format('Y-m-d H:i:s') ?? 'N/A',
            '{message}' => $config['message'] ?? 'No specific advice available.',
            '{temperature}' => $aqiData['weather']['temperature'] ?? 'N/A',
            '{humidity}' => $aqiData['weather']['humidity'] ?? 'N/A',
        ]);
    }
}