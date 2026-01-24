<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
@php
    $widgetKey = $widgetConfig['key'] ?? 'unknown';
    $widgetTitle = $widgetConfig['title_ar'] ?? $widgetConfig['title'] ?? __('Module Statistics');
    $widgetIcon = $widgetConfig['icon'] ?? '📊';
    $moduleName = $widgetConfig['module'] ?? '';
    
    // Get module stats from the parent component if available
    $moduleStats = $moduleStatsData[$moduleName] ?? null;
@endphp

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ $widgetIcon }}</span>
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">{{ __($widgetTitle) }}</h3>
        </div>
        @if($moduleName)
        <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 text-xs rounded-full text-slate-600 dark:text-slate-400">
            {{ ucfirst($moduleName) }}
        </span>
        @endif
    </div>
    
    @if($moduleStats && !empty($moduleStats['total_products']))
        {{-- Display actual stats --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($moduleStats['total_products'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Products') }}</p>
            </div>
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($moduleStats['total_value'] ?? 0, 0) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Total Value') }}</p>
            </div>
            @if(isset($moduleStats['low_stock']))
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-2xl font-bold {{ ($moduleStats['low_stock'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400' }}">{{ number_format($moduleStats['low_stock'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Low Stock') }}</p>
            </div>
            @endif
            @if(isset($moduleStats['this_month_sales']))
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($moduleStats['this_month_sales'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Sales This Month') }}</p>
            </div>
            @endif
        </div>
    @else
        {{-- Placeholder when no data --}}
        <div class="flex flex-col items-center justify-center py-6 text-slate-400 dark:text-slate-500">
            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-sm">{{ __('No data available') }}</p>
        </div>
    @endif
</div>