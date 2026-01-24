<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">{{ __('Recent Sales') }}</h3>
        @can('sales.view')
        <a href="{{ route('admin.reports.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700">{{ __('View All') }}</a>
        @endcan
    </div>
    @if(count($recentSales) > 0)
        <div class="space-y-3">
            @foreach($recentSales as $sale)
                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">{{ $sale['reference'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $sale['customer'] }} - {{ $sale['date'] }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $sale['total'] }} {{ __('EGP') }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full 
                            @if($sale['status'] === 'completed') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @elseif($sale['status'] === 'pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @endif">
                            {{ ucfirst($sale['status']) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-slate-400 dark:text-slate-500">
            <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm">{{ __('No recent sales') }}</p>
        </div>
    @endif
</div>
