<?php

namespace App\Livewire;

use App\Services\WhatsAppNotificationService;
use Livewire\Attributes\On;
use Livewire\Component;

class WhatsAppSessionManager extends Component
{
    public array $sessionInfo = [];
    public ?string $qrCode = null;
    public string $testRecipient = '';
    public string $statusMessage = '';
    public string $statusType = 'info'; // success, error, info, warning

    protected WhatsAppNotificationService $whatsAppService;

    public function boot(WhatsAppNotificationService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function mount()
    {
        $this->loadSessionInfo();
    }

    #[On('refresh-session')]
    public function loadSessionInfo()
    {
        $this->sessionInfo = $this->whatsAppService->getSessionInfo();

        if ($this->sessionInfo['needs_qr']) {
            $this->loadQrCode();
        }
    }

    public function loadQrCode()
    {
        $qrResult = $this->whatsAppService->getQrCode();

        if ($qrResult['success']) {
            $this->qrCode = $qrResult['image'] ?? $qrResult['qr_code'] ?? null;
            $this->setStatus('QR Code loaded. Please scan with your WhatsApp mobile app.', 'info');
        } else {
            $this->setStatus('Failed to load QR code: ' . ($qrResult['error'] ?? 'Unknown error'), 'error');
        }
    }

    public function startSession()
    {
        $result = $this->whatsAppService->startSession();

        if ($result['success']) {
            $this->setStatus('Session started successfully!', 'success');
            $this->loadSessionInfo();
        } else {
            $this->setStatus('Failed to start session: ' . ($result['error'] ?? $result['message']), 'error');
        }
    }

    public function stopSession()
    {
        $result = $this->whatsAppService->stopSession();

        if ($result['success']) {
            $this->setStatus('Session stopped successfully!', 'success');
            $this->qrCode = null;
            $this->loadSessionInfo();
        } else {
            $this->setStatus('Failed to stop session: ' . ($result['error'] ?? $result['message']), 'error');
        }
    }

    public function restartSession()
    {
        $result = $this->whatsAppService->restartSession();

        if ($result['success']) {
            $this->setStatus('Session restarted successfully!', 'success');
        } else {
            $this->setStatus('Failed to restart session: ' . ($result['error'] ?? $result['message']), 'error');
        }

        $this->loadSessionInfo();
    }

    public function refreshStatus()
    {
        $this->loadSessionInfo();
        $this->setStatus('Status refreshed.', 'info');
    }

    public function sendTestMessage()
    {
        if (empty($this->testRecipient)) {
            $this->setStatus('Please enter a recipient number.', 'error');
            return;
        }

        if (!$this->sessionInfo['ready']) {
            $this->setStatus('Session is not ready. Please start and authenticate the session first.', 'error');
            return;
        }

        $result = $this->whatsAppService->sendTestMessage($this->testRecipient);

        if ($result['success']) {
            $this->setStatus("Test message sent successfully to {$this->testRecipient}!", 'success');
            $this->testRecipient = '';
        } else {
            $this->setStatus('Failed to send test message: ' . ($result['error'] ?? 'Unknown error'), 'error');
        }
    }

    protected function setStatus(string $message, string $type = 'info')
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    #[On('clear-status')]
    public function clearStatus()
    {
        $this->statusMessage = '';
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

    public function getSessionStatusText(): string
    {
        return match ($this->sessionInfo['status'] ?? 'UNKNOWN') {
            'WORKING' => 'Connected & Ready',
            'STARTING' => 'Starting...',
            'SCAN_QR_CODE' => 'Scan QR Code',
            'FAILED' => 'Failed',
            'ERROR' => 'Error',
            'CONNECTION_ERROR' => 'Connection Error',
            default => 'Unknown Status'
        };
    }

    public function render()
    {
        return view('livewire.whats-app-session-manager');
    }
}
