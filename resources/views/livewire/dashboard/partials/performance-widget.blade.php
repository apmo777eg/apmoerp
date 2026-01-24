<div class="grid gap-4 lg:grid-cols-3">
    @php
        $weeklyChange = $trendIndicators['weekly_sales']['change'] ?? 0;
        $weeklyDirectionClass = $weeklyChange >= 0 ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400' : 'text-rose-600 bg-rose-50 dark:bg-rose-900/30 dark:text-rose-400';
    @endphp
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Weekly sales change') }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ $weeklyChange }}%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ __('Current week') }}: {{ $trendIndicators['weekly_sales']['current'] ?? '0.00' }}
                    · {{ __('Previous') }}: {{ $trendIndicators['weekly_sales']['previous'] ?? '0.00' }}
                </p>
            </div>
            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold {{ $weeklyDirectionClass }}">
                @if($weeklyChange >= 0)
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    {{ __('Up vs last week') }}
                @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    {{ __('Down vs last week') }}
                @endif
            </span>
        </div>
    </div>

    @php
        $inventoryHealth = $trendIndicators['inventory_health'] ?? 100;
    @endphp
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Inventory health') }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ $inventoryHealth }}%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Based on low stock alerts vs total items.') }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-semibold">
                {{ max(0, min(99, $inventoryHealth)) }}%
            </div>
        </div>
        <div class="mt-3 h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600" style="width: {{ max(0, min(100, $inventoryHealth)) }}%"></div>
        </div>
    </div>

    @php
        $invoiceClearRate = $trendIndicators['invoice_clear_rate'] ?? 0;
        $clearClass = $invoiceClearRate >= 80 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($invoiceClearRate >= 50 ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400');
    @endphp
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Invoice clearance') }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ $invoiceClearRate }}%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Completed invoices vs total recorded.') }}</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $clearClass }}">
                {{ $invoiceClearRate >= 80 ? __('Healthy') : ($invoiceClearRate >= 50 ? __('Needs attention') : __('Action required')) }}
            </span>
        </div>
        <div class="mt-3 h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-emerald-400 to-sky-500" style="width: {{ max(0, min(100, $invoiceClearRate)) }}%"></div>
        </div>
    </div>
</div>
