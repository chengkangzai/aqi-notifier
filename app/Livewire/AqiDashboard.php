<?php

namespace App\Livewire;

use App\Models\AqiReading;
use App\Models\NotificationLog;
use App\Services\AqiService;
use App\Services\AqiNotifierService;
use App\Services\WhatsAppNotificationService;
use Livewire\Attributes\On;
use Livewire\Component;

class AqiDashboard extends Component
{
    public ?array $currentAqi = null;
    public array $statistics = [];
    public array $recentNotifications = [];
    public array $sessionInfo = [];
    public string $statusMessage = '';
    public string $statusType = 'info';

    protected AqiService $aqiService;
    protected AqiNotifierService $notifierService;
    protected WhatsAppNotificationService $whatsAppService;

    public function boot(
        AqiService $aqiService,
        AqiNotifierService $notifierService,
        WhatsAppNotificationService $whatsAppService
    ) {
        $this->aqiService = $aqiService;
        $this->notifierService = $notifierService;
        $this->whatsAppService = $whatsAppService;
    }

    public function mount()
    {
        $this->loadData();
    }

    #[On('refresh-dashboard')]
    public function loadData()
    {
        // Get current AQI data
        $this->currentAqi = $this->aqiService->getAqiData();

        // Get statistics
        $this->statistics = $this->notifierService->getAqiStatistics(7);

        // Get recent notifications
        $this->recentNotifications = $this->notifierService->getRecentNotifications(10);

        // Get WhatsApp session status
        $this->sessionInfo = $this->whatsAppService->getSessionInfo();
    }

    public function refreshData()
    {
        $this->loadData();
        $this->setStatus('Data refreshed successfully!', 'success');
    }

    public function manualAqiCheck()
    {
        $result = $this->notifierService->checkAndNotify();

        if ($result['success']) {
            $notificationsSent = count($result['notifications_sent'] ?? []);
            if ($notificationsSent > 0) {
                $this->setStatus("Manual AQI check completed. {$notificationsSent} notifications sent.", 'success');
            } else {
                $this->setStatus('Manual AQI check completed. No notifications were needed.', 'info');
            }
        } else {
            $this->setStatus('Manual AQI check failed: ' . $result['message'], 'error');
        }

        $this->loadData();
    }

    public function getAqiLevelColor(?string $level): string
    {
        if (!$level) return 'gray';

        $config = $this->aqiService->getAqiLevelConfig($level);
        return $config['color'] ?? 'gray';
    }

    public function getAqiLevelText(?string $level): string
    {
        if (!$level) return 'Unknown';

        return ucfirst(str_replace('_', ' ', $level));
    }

    public function getSessionStatusColor(): string
    {
        return match ($this->sessionInfo['status'] ?? 'UNKNOWN') {
            'WORKING' => 'green',
            'STARTING', 'SCAN_QR_CODE' => 'yellow',
            'FAILED', 'ERROR' => 'red',
            default => 'gray'
        };
    }

    protected function setStatus(string $message, string $type = 'info')
    {
        $this->statusMessage = $message;
        $this->statusType = $type;;
    }

    #[On('clear-status')]
    public function clearStatus()
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.aqi-dashboard');
    }
}
