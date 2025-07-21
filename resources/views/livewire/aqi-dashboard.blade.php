<div class="w-full space-y-6">
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

    {{-- Current AQI Status --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Main AQI Card --}}
        <div class="md:col-span-2">
            <div class="h-full bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                @if($currentAqi)
                    <div class="text-center">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $currentAqi['city'] }}</h2>
                        <div class="mb-4">
                            <div class="text-6xl font-bold mb-2" style="color: {{ $this->getAqiLevelColor($currentAqi['level']) }};">
                                {{ $currentAqi['aqi'] }}
                            </div>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{
                                $this->getAqiLevelColor($currentAqi['level']) === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                ($this->getAqiLevelColor($currentAqi['level']) === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                ($this->getAqiLevelColor($currentAqi['level']) === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'))
                            }}">
                                {{ $this->getAqiLevelText($currentAqi['level']) }}
                            </span>
                        </div>
                        
                        {{-- Weather Info --}}
                        @if(!empty($currentAqi['weather']))
                            <div class="grid grid-cols-2 gap-4 mt-6">
                                @if(isset($currentAqi['weather']['temperature']))
                                    <div class="text-center">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Temperature</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $currentAqi['weather']['temperature'] }}°C</p>
                                    </div>
                                @endif
                                
                                @if(isset($currentAqi['weather']['humidity']))
                                    <div class="text-center">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Humidity</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $currentAqi['weather']['humidity'] }}%</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        @if(isset($currentAqi['time']))
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                                Last updated: {{ $currentAqi['time']->format('Y-m-d H:i:s') }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">Unable to load AQI data</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h3>
                <div class="mt-4 space-y-3">
                    <flux:button 
                        wire:click="refreshData"
                        variant="primary"
                        class="w-full"
                    >
                        Refresh Data
                    </flux:button>
                    
                    <flux:button 
                        wire:click="manualAqiCheck"
                        variant="filled"
                        class="w-full"
                    >
                        Manual AQI Check
                    </flux:button>
                </div>
            </div>

            {{-- WhatsApp Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100">WhatsApp Status</h3>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Connection:</span>
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{
                        $this->getSessionStatusColor() === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                        ($this->getSessionStatusColor() === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                        ($this->getSessionStatusColor() === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'))
                    }}">
                        {{ $sessionInfo['ready'] ? 'Ready' : 'Not Ready' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">7-Day Average</p>
            <div class="text-2xl font-bold mt-1 text-gray-900 dark:text-gray-100">
                {{ $statistics['average_aqi'] ?? 'N/A' }}
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">7-Day Max</p>
            <div class="text-2xl font-bold mt-1 text-red-600">
                {{ $statistics['max_aqi'] ?? 'N/A' }}
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">7-Day Min</p>
            <div class="text-2xl font-bold mt-1 text-green-600">
                {{ $statistics['min_aqi'] ?? 'N/A' }}
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Readings</p>
            <div class="text-2xl font-bold mt-1 text-gray-900 dark:text-gray-100">
                {{ $statistics['count'] ?? 0 }}
            </div>
        </div>
    </div>

    {{-- Recent Notifications --}}
    @if(count($recentNotifications) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Notifications</h3>
            <div class="mt-4 space-y-3">
                @foreach(array_slice($recentNotifications, 0, 5) as $notification)
                    <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{
                                $this->getAqiLevelColor($notification['aqi_level']) === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                ($this->getAqiLevelColor($notification['aqi_level']) === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                ($this->getAqiLevelColor($notification['aqi_level']) === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'))
                            }}">
                                {{ $this->getAqiLevelText($notification['aqi_level']) }}
                            </span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">AQI {{ $notification['aqi_value'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    to {{ substr($notification['recipient'], 0, 5) }}...
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{
                                $notification['status'] === 'sent' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                            }}">
                                {{ ucfirst($notification['status']) }}
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($notification['sent_at'])->format('M j, H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Pollutants Breakdown --}}
    @if($currentAqi && !empty($currentAqi['pollutants']))
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pollutants Breakdown</h3>
            <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($currentAqi['pollutants'] as $pollutant => $value)
                    <div class="text-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <p class="text-sm text-gray-500 dark:text-gray-400 uppercase">{{ $pollutant }}</p>
                        <div class="text-lg font-semibold mt-1 text-gray-900 dark:text-gray-100">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            
            @if($currentAqi['dominant_pollutant'])
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-950 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>Dominant Pollutant:</strong> {{ strtoupper($currentAqi['dominant_pollutant']) }}
                    </p>
                </div>
            @endif
        </div>
    @endif

    {{-- Auto-refresh script --}}
    <script>
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            @this.dispatch('refresh-dashboard');
        }, 300000);
    </script>
</div>