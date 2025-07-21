<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('WhatsApp Session')" :subheading="__('Manage WhatsApp connection for AQI notifications')">
        <div class="w-full space-y-6">
            {{-- Status Alert --}}
            @if($statusMessage)
                <div class="rounded-md p-4 {{
                    $statusType === 'success' ? 'border border-green-300 bg-green-50 text-green-800 dark:border-green-600 dark:bg-green-900 dark:text-green-200' :
                    ($statusType === 'error' ? 'border border-red-300 bg-red-50 text-red-800 dark:border-red-600 dark:bg-red-900 dark:text-red-200' :
                    ($statusType === 'warning' ? 'border border-yellow-300 bg-yellow-50 text-yellow-800 dark:border-yellow-600 dark:bg-yellow-900 dark:text-yellow-200' :
                    'border border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-600 dark:bg-blue-900 dark:text-blue-200'))
                }}">
                    {{ $statusMessage }}
                </div>
            @endif

            {{-- Session Status --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Session Status</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Session: {{ $sessionInfo['session_name'] ?? 'default' }}</p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{
                            $this->getSessionStatusColor() === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                            ($this->getSessionStatusColor() === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                            ($this->getSessionStatusColor() === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'))
                        }}">
                            {{ $this->getSessionStatusText() }}
                        </span>

                        <flux:button
                            wire:click="refreshStatus"
                            variant="ghost"
                            size="sm">
                            Refresh
                        </flux:button>
                    </div>
                </div>

                @if(isset($sessionInfo['last_checked']))
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Last checked: {{ \Carbon\Carbon::parse($sessionInfo['last_checked'])->format('Y-m-d H:i:s') }}
                    </p>
                @endif
            </div>

            <flux:separator />

            {{-- Session Controls --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Session Controls</h3>

                <div class="flex flex-wrap gap-3">
                    @if(!($sessionInfo['ready'] ?? false))
                        <flux:button
                            wire:click="startSession"
                            variant="primary">
                            Start Session
                        </flux:button>
                    @endif

                    @if($sessionInfo['ready'] ?? false)
                        <flux:button
                            wire:click="stopSession"
                            variant="danger">
                            Stop Session
                        </flux:button>
                    @endif

                    <flux:button
                        wire:click="restartSession"
                        variant="filled">
                        Restart Session
                    </flux:button>
                </div>

                @if(($sessionInfo['error'] ?? null))
                    <div class="mt-4 rounded-md border border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-600 dark:bg-red-900 dark:text-red-200">
                        <strong>Error:</strong> {{ $sessionInfo['error'] }}
                    </div>
                @endif
            </div>

            {{-- QR Code Display --}}
            @if($qrCode && ($sessionInfo['needs_qr'] ?? false))
                <flux:separator />

                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">QR Code Authentication</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Scan this QR code with your WhatsApp mobile app</p>

                    <div class="flex justify-center mb-4">
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white">
                            <img
                                src="data:image/png;base64,{{ $qrCode }}"
                                alt="WhatsApp QR Code"
                                class="w-64 h-64 max-w-full" />
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <flux:button
                            wire:click="loadQrCode"
                            variant="ghost"
                            size="sm">
                            Reload QR Code
                        </flux:button>
                    </div>
                </div>
            @endif

            <flux:separator />

            {{-- Test Message Section --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Test Message</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Send a test message to verify the connection</p>

                <form wire:submit.prevent="sendTestMessage" class="space-y-4">
                    <flux:input
                        wire:model="testRecipient"
                        type="tel"
                        label="Recipient WhatsApp Number"
                        placeholder="+60123456789 or 60123456789"
                        description="Enter the WhatsApp number (with country code)"
                        required />

                    <div class="flex items-center space-x-3">
                        <flux:button
                            type="submit"
                            variant="primary"
                            :disabled="!($sessionInfo['ready'] ?? false)">
                            Send Test Message
                        </flux:button>

                        @if(!($sessionInfo['ready'] ?? false))
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Session must be active to send messages
                            </p>
                        @endif
                    </div>
                </form>
            </div>

            <flux:separator />

            {{-- Session Information --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Session Information</h3>

                <dl class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Session Name:</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $sessionInfo['session_name'] ?? 'N/A' }}</dd>
                    </div>

                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $sessionInfo['status'] ?? 'N/A' }}</dd>
                    </div>

                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Ready:</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ ($sessionInfo['ready'] ?? false) ? 'Yes' : 'No' }}</dd>
                    </div>

                    <div class="flex justify-between py-2">
                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Needs QR:</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ ($sessionInfo['needs_qr'] ?? false) ? 'Yes' : 'No' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Auto-refresh script --}}
            <script>
                // Auto-refresh session status every 30 seconds
                setInterval(function() {
                    @this.dispatch('refresh-session');
                }, 30000);
            </script>
        </div>
    </x-settings.layout>
</section>
