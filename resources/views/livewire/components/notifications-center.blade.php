<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    {{-- Notification Bell Button --}}
    <button 
        @click="open = !open" 
        class="relative p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors"
        aria-label="{{ __('Notifications') }}"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full min-w-[20px]">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown Panel --}}
    <div 
        x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-96 max-w-sm z-popover"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            {{-- Header --}}
            <div class="px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white flex items-center justify-between">
                <h3 class="font-semibold text-lg">{{ __('Notifications') }}</h3>
                @if($unreadCount > 0)
                    <button 
                        wire:click="markAllAsRead" 
                        class="text-xs text-white/90 hover:text-white underline"
                    >
                        {{ __('Mark all as read') }}
                    </button>
                @endif
            </div>

            {{-- Notifications List --}}
            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                @forelse($notifications as $notification)
                    <div class="p-4 hover:bg-slate-50 transition-colors {{ !$notification['read'] ? 'bg-blue-50/50' : '' }}">
                        <div class="flex gap-3">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                @php
                                    $bgClass = match($notification['color']) {
                                        'green' => 'bg-green-100',
                                        'blue' => 'bg-blue-100',
                                        'purple' => 'bg-purple-100',
                                        'rose' => 'bg-rose-100',
                                        'emerald' => 'bg-emerald-100',
                                        default => 'bg-gray-100'
                                    };
                                    $textClass = match($notification['color']) {
                                        'green' => 'text-green-600',
                                        'blue' => 'text-blue-600',
                                        'purple' => 'text-purple-600',
                                        'rose' => 'text-rose-600',
                                        'emerald' => 'text-emerald-600',
                                        default => 'text-gray-600'
                                    };
                                @endphp
                                <div class="w-10 h-10 rounded-full {{ $bgClass }} flex items-center justify-center">
                                    <svg class="w-5 h-5 {{ $textClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $notification['icon'] }}"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900">
                                    {{ $notification['title'] }}
                                </p>
                                @if($notification['message'])
                                    <p class="text-xs text-slate-600 mt-1">
                                        {{ $notification['message'] }}
                                    </p>
                                @endif
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $notification['time'] }}
                                </p>

                                {{-- Actions --}}
                                <div class="flex gap-2 mt-2">
                                    @if($notification['action_url'])
                                        <a 
                                            href="{{ $notification['action_url'] }}" 
                                            class="text-xs text-emerald-600 hover:text-emerald-700 font-medium"
                                        >
                                            {{ __('View') }} →
                                        </a>
                                    @endif
                                    @if(!$notification['read'])
                                        <button 
                                            wire:click="markAsRead('{{ $notification['id'] }}')"
                                            class="text-xs text-slate-500 hover:text-slate-700"
                                        >
                                            {{ __('Mark as read') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-sm">{{ __('No notifications') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100">
                <button 
                    wire:click="loadNotifications" 
                    class="text-sm text-emerald-600 hover:text-emerald-700 font-medium w-full text-center"
                >
                    {{ __('Refresh') }}
                </button>
            </div>
        </div>
    </div>
</div>

@script
    // NEW-01 FIX: Use Livewire's component-scoped cleanup mechanism
    // The $cleanup function may not be available in all Livewire 4.0.x versions
    // Using a guard pattern with livewire:navigating event for cleanup instead
    const componentId = $wire.__instance?.id ?? 'notifications-center-' + Math.random().toString(36).substr(2, 9);
    const timerKey = `notifications-center:${componentId}`;
    
    // Initialize global timer storage if not exists
    window.__lwTimers = window.__lwTimers || {};
    
    // Clear any existing timer for this component
    if (window.__lwTimers[timerKey]) {
        clearInterval(window.__lwTimers[timerKey]);
    }
    
    // Set up auto-refresh every 60 seconds
    window.__lwTimers[timerKey] = setInterval(() => {
        $wire.call('loadNotifications');
    }, 60000);
    
    // Clean up when navigating away (Livewire 4 SPA navigation)
    const cleanup = () => {
        if (window.__lwTimers[timerKey]) {
            clearInterval(window.__lwTimers[timerKey]);
            delete window.__lwTimers[timerKey];
        }
    };
    
    // Listen for navigation events to clean up
    document.addEventListener('livewire:navigating', cleanup, { once: true });
@endscript
