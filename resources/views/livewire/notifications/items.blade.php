<div class="space-y-2">
    @if(count($notifications) > 0)
        <div class="flex items-center justify-between px-4 py-2 border-b border-slate-200 dark:border-slate-700">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Notifications') }}</span>
            <button type="button" wire:click="markAllAsRead" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-500">
                {{ __('Mark all as read') }}
            </button>
        </div>
        @foreach($notifications as $notification)
            <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer">
                <div class="flex-shrink-0 w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5V9a6 6 0 10-12 0v3l-5 5h5m7 0v1a3 3 0 01-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    @if($notification['link'])
                        <a href="{{ $notification['link'] }}" wire:click="markAsRead(@js($notification['id']))" class="text-sm text-slate-900 dark:text-slate-100 hover:text-emerald-600">
                            {{ $notification['message'] }}
                        </a>
                    @else
                        <p class="text-sm text-slate-900 dark:text-slate-100">{{ $notification['message'] }}</p>
                    @endif
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $notification['created_at'] }}</p>
                </div>
                <button type="button" wire:click="markAsRead(@js($notification['id']))" class="flex-shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" title="{{ __('Mark as read') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </div>
        @endforeach
    @else
        <div class="text-center py-8 px-4">
            <div class="text-4xl mb-2">🔔</div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No new notifications') }}</p>
        </div>
    @endif
</div>
