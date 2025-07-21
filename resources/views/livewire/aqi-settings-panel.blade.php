<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('AQI Settings')" :subheading="__('Configure notification thresholds and recipients')">
        <form wire:submit="saveSettings" class="w-full space-y-6">
            {{-- Status Alert --}}
            @if($statusMessage)
                <div class="rounded-md p-4 {{
                    $statusType === 'success' ? 'border border-green-300 bg-green-50 text-green-800 dark:border-green-600 dark:bg-green-900 dark:text-green-200' :
                    ($statusType === 'error' ? 'border border-red-300 bg-red-50 text-red-800 dark:border-red-600 dark:bg-red-900 dark:text-red-200' :
                    'border border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-600 dark:bg-blue-900 dark:text-blue-200')
                }}">
                    {{ $statusMessage }}
                </div>
            @endif

            {{-- AQI Thresholds --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">AQI Notification Thresholds</h3>
                <div class="space-y-3">
                    @foreach($thresholds as $level => $config)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-4 h-4 rounded-full" style="background-color: {{ $config['color'] ?? 'gray' }};"></div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ ucfirst(str_replace('_', ' ', $level)) }}
                                    </span>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        AQI {{ $config['min'] }} - {{ $config['max'] }}
                                    </p>
                                </div>
                            </div>

                            <flux:switch
                                wire:model="thresholds.{{ $level }}.notify"
                                wire:change="updateThresholdNotify('{{ $level }}', $event.target.checked)" />
                        </div>
                    @endforeach
                </div>
            </div>

            <flux:separator />

            {{-- Recipients Management --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Notification Recipients</h3>

                <div class="flex space-x-3 mb-4">
                    <flux:input
                        wire:model="newRecipient"
                        type="tel"
                        placeholder="+60123456789"
                        class="flex-1" />
                    <flux:button
                        wire:click.prevent="addRecipient"
                        variant="primary"
                        size="sm">
                        Add
                    </flux:button>
                </div>

                @if(count($recipients) > 0)
                    <div class="space-y-2">
                        @foreach($recipients as $index => $recipient)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $recipient }}</span>
                                <flux:button
                                    wire:click="removeRecipient({{ $index }})"
                                    variant="ghost"
                                    size="sm"
                                    class="text-red-600 hover:text-red-700">
                                    Remove
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No recipients configured</p>
                @endif
            </div>

            <flux:separator />

            {{-- Notification Settings --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Notification Settings</h3>

                <div class="space-y-4">
                    <flux:input
                        wire:model="rateLimitMinutes"
                        type="number"
                        min="1"
                        max="1440"
                        label="Rate Limit (minutes)"
                        description="Minimum time between notifications for the same AQI level" />

                    <div class="space-y-4">
                        <flux:checkbox
                            wire:model.live="quietHoursEnabled"
                            label="Enable Quiet Hours" />

                        @if($quietHoursEnabled)
                            <div class="grid grid-cols-2 gap-4 ml-6">
                                <flux:input
                                    wire:model="quietStart"
                                    type="time"
                                    label="Start Time" />
                                <flux:input
                                    wire:model="quietEnd"
                                    type="time"
                                    label="End Time" />
                            </div>
                            <p class="ml-6 text-sm text-gray-500 dark:text-gray-400">
                                No notifications will be sent during quiet hours (Asia/Kuala_Lumpur timezone)
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <flux:button type="submit" variant="primary">
                    {{ __('Save All Settings') }}
                </flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
