<?php

namespace App\Console\Commands;

use App\Services\AqiNotifierService;
use Illuminate\Console\Command;

class CheckAqi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aqi:check 
                            {--city= : City to check (defaults to Kuala Lumpur)}
                            {--force : Force notification regardless of thresholds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check current AQI levels and send notifications if needed';

    protected AqiNotifierService $notifierService;

    public function __construct(AqiNotifierService $notifierService)
    {
        parent::__construct();
        $this->notifierService = $notifierService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌬️ Starting AQI check...');
        
        $city = $this->option('city');
        
        try {
            // Always use stored recipients from settings (no manual override)
            $result = $this->notifierService->checkAndNotify($city, null, $this->option('force'));
            
            if (!$result['success']) {
                $this->error('❌ AQI check failed: ' . $result['message']);
                return self::FAILURE;
            }
            
            // Display AQI information
            $aqiData = $result['aqi_data'];
            if ($aqiData) {
                $this->newLine();
                $this->info("📍 City: {$aqiData['city']}");
                $this->info("📊 Current AQI: {$aqiData['aqi']}");
                $this->info("🎯 Level: " . ucfirst(str_replace('_', ' ', $aqiData['level'])));
                
                if (isset($aqiData['time'])) {
                    $this->info("🕐 Reading Time: {$aqiData['time']->format('Y-m-d H:i:s')}");
                }
                
                if (isset($aqiData['weather']['temperature'])) {
                    $this->info("🌡️ Temperature: {$aqiData['weather']['temperature']}°C");
                }
                
                if (isset($aqiData['weather']['humidity'])) {
                    $this->info("💧 Humidity: {$aqiData['weather']['humidity']}%");
                }
            }
            
            // Display notification results
            $notificationsSent = $result['notifications_sent'] ?? [];
            if (count($notificationsSent) > 0) {
                $this->newLine();
                $this->info('📱 Notifications sent:');
                
                foreach ($notificationsSent as $notification) {
                    $status = $notification['success'] ? '✅' : '❌';
                    $recipient = substr($notification['recipient'], 0, 8) . '...';
                    $this->line("  {$status} {$recipient}");
                    
                    if (!$notification['success'] && isset($notification['error'])) {
                        $this->error("    Error: {$notification['error']}");
                    }
                }
            } else {
                $this->info('ℹ️ No notifications were sent (thresholds not met or rate limited)');
            }
            
            $this->newLine();
            $this->info('✅ AQI check completed successfully');
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('💥 Unexpected error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
