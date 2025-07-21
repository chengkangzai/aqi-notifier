<?php

namespace App\Services;

use CCK\LaravelWahaSaloonSdk\Waha\Waha;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;

class WhatsAppNotificationService
{
    protected Waha $waha;
    protected string $sessionName;

    public function __construct()
    {
        $this->waha = new Waha();
        $this->sessionName = config('aqi.notifications.session_name', 'default');
    }

    /**
     * Check WhatsApp session status
     */
    public function getSessionStatus(): array
    {
        try {
            $response = $this->waha->sessions()->getSessionInformation($this->sessionName);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => $data['status'] ?? 'UNKNOWN',
                    'name' => $data['name'] ?? $this->sessionName,
                    'config' => $data['config'] ?? null,
                    'ready' => $data['status'] === 'WORKING',
                    'raw_response' => $data
                ];
            }

            return [
                'status' => 'ERROR',
                'name' => $this->sessionName,
                'ready' => false,
                'error' => $response->body(),
                'http_status' => $response->status()
            ];

        } catch (RequestException $e) {
            Log::error('Failed to get WhatsApp session status', [
                'session' => $this->sessionName,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'CONNECTION_ERROR',
                'name' => $this->sessionName,
                'ready' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Start WhatsApp session
     */
    public function startSession(): array
    {
        try {
            Log::info("Starting WhatsApp session: {$this->sessionName}");

            $response = $this->waha->sessions()->startTheSession($this->sessionName);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("WhatsApp session started successfully", ['session' => $this->sessionName]);

                return [
                    'success' => true,
                    'message' => 'Session started successfully',
                    'data' => $data
                ];
            }

            Log::error("Failed to start WhatsApp session", [
                'session' => $this->sessionName,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to start session',
                'error' => $response->body(),
                'http_status' => $response->status()
            ];

        } catch (RequestException $e) {
            Log::error('Exception while starting WhatsApp session', [
                'session' => $this->sessionName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Connection error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop WhatsApp session
     */
    public function stopSession(): array
    {
        try {
            Log::info("Stopping WhatsApp session: {$this->sessionName}");

            $response = $this->waha->sessions()->stopTheSession($this->sessionName);

            if ($response->successful()) {
                Log::info("WhatsApp session stopped successfully", ['session' => $this->sessionName]);

                return [
                    'success' => true,
                    'message' => 'Session stopped successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to stop session',
                'error' => $response->body()
            ];

        } catch (RequestException $e) {
            Log::error('Exception while stopping WhatsApp session', [
                'session' => $this->sessionName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Connection error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get QR code for WhatsApp authentication
     */
    public function getQrCode(): ?array
    {
        try {
            $response = $this->waha->auth()->getQrCodeBase64($this->sessionName);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'qr_code' => $data['data'] ?? null,
                    'image' => $data['data'] ?? null,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (RequestException $e) {
            Log::error('Failed to get QR code', [
                'session' => $this->sessionName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send text message to WhatsApp number
     */
    public function sendMessage(string $recipient, string $message): array
    {
        try {
            // Ensure recipient is in correct format
            $chatId = $this->formatRecipientId($recipient);

            Log::info("Sending WhatsApp message", [
                'recipient' => $chatId,
                'session' => $this->sessionName,
                'message_length' => strlen($message)
            ]);

            $response = $this->waha->sendText()->sendTextMessage(
                chatId: $chatId,
                text: $message,
                session: $this->sessionName,
                replyTo: null,
                linkPreview: null,
                linkPreviewHighQuality: null
            );

            if ($response->successful()) {
                $data = $response->json();

                Log::info("WhatsApp message sent successfully", [
                    'recipient' => $chatId,
                    'message_id' => $data['id'] ?? 'unknown'
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['id'] ?? null,
                    'data' => $data
                ];
            }

            Log::error("Failed to send WhatsApp message", [
                'recipient' => $chatId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'http_status' => $response->status()
            ];

        } catch (RequestException $e) {
            Log::error('Exception while sending WhatsApp message', [
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send test message to verify connection
     */
    public function sendTestMessage(string $recipient): array
    {
        $testMessage = "🤖 *AQI Notifier Test*\n\nThis is a test message from your AQI notification system.\n\nIf you received this message, your WhatsApp integration is working correctly!\n\n⏰ Sent at: " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($recipient, $testMessage);
    }

    /**
     * Format recipient phone number to WhatsApp chat ID format
     */
    protected function formatRecipientId(string $recipient): string
    {
        // Remove any non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $recipient);

        // Add @c.us suffix if not already present
        if (!str_contains($recipient, '@')) {
            return $cleaned . '@c.us';
        }

        return $recipient;
    }

    /**
     * Check if session is ready for sending messages
     */
    public function isSessionReady(): bool
    {
        $status = $this->getSessionStatus();
        return $status['ready'] ?? false;
    }

    /**
     * Get session information for UI display
     */
    public function getSessionInfo(): array
    {
        $status = $this->getSessionStatus();

        return [
            'session_name' => $this->sessionName,
            'status' => $status['status'] ?? 'UNKNOWN',
            'ready' => $status['ready'] ?? false,
            'needs_qr' => in_array($status['status'] ?? '', ['SCAN_QR_CODE', 'STARTING']),
            'error' => $status['error'] ?? null,
            'last_checked' => now()->toISOString()
        ];
    }

    /**
     * Restart session if needed
     */
    public function restartSession(): array
    {
        Log::info("Restarting WhatsApp session: {$this->sessionName}");

        // Stop current session
        $stopResult = $this->stopSession();

        // Wait a moment before starting
        sleep(2);

        // Start new session
        $startResult = $this->startSession();

        return [
            'success' => $startResult['success'] ?? false,
            'message' => $startResult['success']
                ? 'Session restarted successfully'
                : 'Failed to restart session',
            'stop_result' => $stopResult,
            'start_result' => $startResult
        ];
    }
}
