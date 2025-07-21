<?php

namespace App\Livewire;

use App\Models\AqiSetting;
use App\Services\AqiNotifierService;
use Livewire\Component;

class AqiSettingsPanel extends Component
{
    public array $thresholds = [];
    public array $recipients = [];
    public string $newRecipient = '';
    public bool $quietHoursEnabled = false;
    public string $quietStart = '22:00';
    public string $quietEnd = '07:00';
    public int $rateLimitMinutes = 60;
    public string $statusMessage = '';
    public string $statusType = 'info';

    protected AqiNotifierService $notifierService;

    public function boot(AqiNotifierService $notifierService)
    {
        $this->notifierService = $notifierService;
    }

    public function mount()
    {
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $this->thresholds = AqiSetting::get('thresholds', config('aqi.thresholds'));
        $this->recipients = AqiSetting::get('recipients', []);
        
        $quietHours = AqiSetting::get('quiet_hours', config('aqi.notifications.quiet_hours'));
        $this->quietHoursEnabled = $quietHours['enabled'] ?? false;
        $this->quietStart = $quietHours['start'] ?? '22:00';
        $this->quietEnd = $quietHours['end'] ?? '07:00';
        
        $this->rateLimitMinutes = AqiSetting::get('rate_limit_minutes', config('aqi.notifications.rate_limit_minutes', 60));
    }

    public function saveSettings()
    {
        $this->notifierService->updateSetting('thresholds', $this->thresholds);
        $this->notifierService->updateSetting('recipients', $this->recipients);
        $this->notifierService->updateSetting('quiet_hours', [
            'enabled' => $this->quietHoursEnabled,
            'start' => $this->quietStart,
            'end' => $this->quietEnd,
            'timezone' => 'Asia/Kuala_Lumpur'
        ]);
        $this->notifierService->updateSetting('rate_limit_minutes', $this->rateLimitMinutes);
        
        $this->setStatus('Settings saved successfully!', 'success');
    }

    public function addRecipient()
    {
        if (empty($this->newRecipient)) {
            $this->setStatus('Please enter a recipient number.', 'error');
            return;
        }
        
        $this->recipients[] = $this->newRecipient;
        $this->newRecipient = '';
        $this->setStatus('Recipient added. Remember to save settings.', 'info');
    }

    public function removeRecipient($index)
    {
        unset($this->recipients[$index]);
        $this->recipients = array_values($this->recipients);
        $this->setStatus('Recipient removed. Remember to save settings.', 'info');
    }

    public function updateThresholdNotify($level, $enabled)
    {
        $this->thresholds[$level]['notify'] = $enabled;
    }

    protected function setStatus(string $message, string $type = 'info')
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    public function render()
    {
        return view('livewire.aqi-settings-panel');
    }
}
