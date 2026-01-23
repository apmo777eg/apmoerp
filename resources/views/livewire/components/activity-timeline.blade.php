<div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800">{{ __('Recent Activity') }}</h3>
        <button 
            wire:click="refresh" 
            class="text-sm text-emerald-600 hover:text-emerald-700 font-medium"
        >
            <svg wire:loading.class="animate-spin" wire:target="refresh" class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span class="ml-1">{{ __('Refresh') }}</span>
        </button>
    </div>

    <div class="space-y-4 max-h-96 overflow-y-auto">
        @forelse($activities as $activity)
            <div class="flex gap-3">
                {{-- Timeline Icon --}}
                <div class="flex-shrink-0">
                    @php
                        $bgClass = match($activity['color']) {
                            'green' => 'bg-green-100',
                            'blue' => 'bg-blue-100',
                            'red' => 'bg-red-100',
                            'purple' => 'bg-purple-100',
                            default => 'bg-gray-100'
                        };
                        $textClass = match($activity['color']) {
                            'green' => 'text-green-600',
                            'blue' => 'text-blue-600',
                            'red' => 'text-red-600',
                            'purple' => 'text-purple-600',
                            default => 'text-gray-600'
                        };
                    @endphp
                    <div class="w-10 h-10 rounded-full {{ $bgClass }} flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $textClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $activity['icon'] }}"/>
                        </svg>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-900">
                        <span class="font-medium">{{ $activity['user'] }}</span>
                        <span class="text-slate-600">{{ $activity['description'] }}</span>
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ $activity['time'] }}</p>
                    
                    @if($activity['url'])
                        <a 
                            href="{{ $activity['url'] }}" 
                            class="text-xs text-emerald-600 hover:text-emerald-700 font-medium mt-1 inline-block"
                        >
                            {{ __('View details') }} →
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm">{{ __('No recent activity') }}</p>
            </div>
        @endforelse
    </div>
</div>

@script
<script>
    // Livewire 4 FIX: Wrap with @script for proper execution timing across SPA navigation
    // Auto-refresh activity every 2 minutes
    const refreshInterval = setInterval(() => {
        $wire.call('refresh');
    }, 120000);
    
    // Clean up on component destroy to prevent memory leaks
    // Use cleanup function that runs when component is removed from DOM
    $cleanup(() => {
        clearInterval(refreshInterval);
    });
</script>
@endscript
