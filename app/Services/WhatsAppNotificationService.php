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
     * Send text message to WhatsApp number with retry logic
     */
    public function sendMessage(string $recipient, string $message): array
    {
        $maxAttempts = 3; // Hardcoded retry attempts
        $baseDelay = 5; // Hardcoded initial delay in seconds
        $useExponentialBackoff = true; // Hardcoded exponential backoff
        $autoRestart = true; // Hardcoded auto restart session

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->attemptSendMessage($recipient, $message, $attempt);
            
            if ($result['success']) {
                return $result;
            }

            // Check if this is a session status error and we should restart
            if ($autoRestart && $this->isSessionStatusError($result)) {
                Log::info("Detected session status error, attempting to restart session", [
                    'recipient' => $this->formatRecipientId($recipient),
                    'attempt' => $attempt,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                $restartResult = $this->handleSessionRestart();
                
                if ($restartResult['success']) {
                    // Give the session time to fully restart before retrying
                    $restartDelay = 10; // Hardcoded session restart delay in seconds
                    Log::info("Session restarted successfully, waiting {$restartDelay}s before retry");
                    sleep($restartDelay);
                    
                    // Retry immediately after restart
                    $retryResult = $this->attemptSendMessage($recipient, $message, $attempt);
                    if ($retryResult['success']) {
                        return $retryResult;
                    }
                } else {
                    Log::warning("Failed to restart session", [
                        'restart_error' => $restartResult['message'] ?? 'Unknown restart error'
                    ]);
                }
            }

            // Don't sleep after the last attempt
            if ($attempt < $maxAttempts) {
                $delay = $useExponentialBackoff 
                    ? $baseDelay * pow(2, $attempt - 1) 
                    : $baseDelay;
                
                Log::info("WhatsApp message failed, retrying in {$delay} seconds", [
                    'recipient' => $this->formatRecipientId($recipient),
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'delay' => $delay,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                sleep($delay);
            }
        }

        // All attempts failed
        Log::error("WhatsApp message failed after all retry attempts", [
            'recipient' => $this->formatRecipientId($recipient),
            'total_attempts' => $maxAttempts,
            'final_error' => $result['error'] ?? 'Unknown error'
        ]);

        return [
            'success' => false,
            'error' => $result['error'] ?? 'All retry attempts failed',
            'attempts' => $maxAttempts,
            'http_status' => $result['http_status'] ?? null
        ];
    }

    /**
     * Attempt to send a single WhatsApp message
     */
    protected function attemptSendMessage(string $recipient, string $message, int $attempt): array
    {
        try {
            // Ensure recipient is in correct format
            $chatId = $this->formatRecipientId($recipient);

            Log::info("Attempting to send WhatsApp message", [
                'recipient' => $chatId,
                'session' => $this->sessionName,
                'message_length' => strlen($message),
                'attempt' => $attempt
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
                    'message_id' => $data['id'] ?? 'unknown',
                    'attempt' => $attempt
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['id'] ?? null,
                    'data' => $data,
                    'attempt' => $attempt
                ];
            }

            Log::warning("WhatsApp message attempt failed", [
                'recipient' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
                'attempt' => $attempt
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'http_status' => $response->status(),
                'attempt' => $attempt
            ];

        } catch (RequestException $e) {
            Log::warning('Exception during WhatsApp message attempt', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'attempt' => $attempt
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'attempt' => $attempt
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

    /**
     * Check if the error is related to session status
     */
    protected function isSessionStatusError(array $result): bool
    {
        $error = $result['error'] ?? '';
        $httpStatus = $result['http_status'] ?? null;

        // HTTP 422 typically indicates session status issues
        if ($httpStatus === 422) {
            return true;
        }

        // Check for specific error messages related to session status
        $sessionErrorPatterns = [
            'Session status is not as expected',
            'status is not as expected',
            'Try again later or restart the session',
            'session is not ready',
            'session not found',
            'STARTING',
            'SCAN_QR_CODE',
            'FAILED'
        ];

        foreach ($sessionErrorPatterns as $pattern) {
            if (stripos($error, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle session restart with proper error handling
     */
    protected function handleSessionRestart(): array
    {
        try {
            Log::info("Attempting to restart WhatsApp session due to status error", [
                'session' => $this->sessionName
            ]);

            $restartResult = $this->restartSession();

            if ($restartResult['success']) {
                Log::info("WhatsApp session restarted successfully", [
                    'session' => $this->sessionName
                ]);
                return [
                    'success' => true,
                    'message' => 'Session restarted successfully'
                ];
            } else {
                Log::error("Failed to restart WhatsApp session", [
                    'session' => $this->sessionName,
                    'restart_result' => $restartResult
                ]);
                return [
                    'success' => false,
                    'message' => $restartResult['message'] ?? 'Unknown restart error'
                ];
            }
        } catch (\Exception $e) {
            Log::error("Exception occurred during session restart", [
                'session' => $this->sessionName,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Exception during restart: ' . $e->getMessage()
            ];
        }
    }
}
