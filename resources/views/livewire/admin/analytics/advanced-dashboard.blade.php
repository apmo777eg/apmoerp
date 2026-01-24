<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ __('Advanced Analytics Dashboard') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ __('View advanced analytics and insights for your business.') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Period Selector --}}
            <select wire:model.live="period"
                class="erp-input rounded-xl text-sm">
                <option value="week">{{ __('Last 7 Days') }}</option>
                <option value="month">{{ __('Last 30 Days') }}</option>
                <option value="quarter">{{ __('Last 90 Days') }}</option>
                <option value="year">{{ __('Last Year') }}</option>
            </select>
        </div>
    </div>

    {{-- Loading State --}}
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-500"></div>
        </div>
    @else
        {{-- Metrics Grid --}}
        @if(!empty($metrics))
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach($metrics as $key => $metric)
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                        <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            {{ is_array($metric) ? ($metric['label'] ?? $key) : $key }}
                        </h3>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                            {{ is_array($metric) ? ($metric['value'] ?? 0) : $metric }}
                        </p>
                        @if(is_array($metric) && isset($metric['change']))
                            <p class="mt-1 text-xs {{ $metric['change'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $metric['change'] >= 0 ? '+' : '' }}{{ $metric['change'] }}%
                                {{ __('vs previous period') }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 text-center">
                <p class="text-slate-500 dark:text-slate-400">
                    {{ __('No analytics data available for the selected period.') }}
                </p>
            </div>
        @endif
    @endif
</div>
