<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">{{ __('Recent Activity') }}</h3>
        @can('logs.audit.view')
        <a href="{{ route('admin.activity-log') }}" class="text-sm text-emerald-600 hover:text-emerald-700">{{ __('View All') }}</a>
        @endcan
    </div>
    @if(count($recentActivities) > 0)
        <div class="space-y-3">
            @foreach($recentActivities as $activity)
                @php
                    $userName = $activity['user'] ?? __('System');
                    $userInitial = mb_strtoupper(mb_substr($userName, 0, 1) ?: 'S');
                @endphp
                <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <div class="flex-shrink-0 w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            {{ $userInitial }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-800 dark:text-white">
                            <span class="font-medium">{{ $userName }}</span>
                        </p>
                        <p class="text-xs text-slate-600 dark:text-slate-400 truncate">
                            {{ $activity['description'] ?? '' }}
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                            {{ $activity['time'] ?? '' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-slate-400 dark:text-slate-500">
            <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm">{{ __('No recent activity') }}</p>
        </div>
    @endif
</div>
