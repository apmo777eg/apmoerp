<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-emerald-100 dark:border-emerald-900 bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 text-white shadow-lg shadow-emerald-500/30">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-100">{{ __("Today's Sales") }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $stats['today_sales'] ?? '0.00' }} {{ __('EGP') }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-blue-100 dark:border-blue-900 bg-gradient-to-br from-blue-500 to-blue-600 p-5 text-white shadow-lg shadow-blue-500/30">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-blue-100">{{ __('This Month') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $stats['month_sales'] ?? '0.00' }} {{ __('EGP') }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-purple-100 dark:border-purple-900 bg-gradient-to-br from-purple-500 to-purple-600 p-5 text-white shadow-lg shadow-purple-500/30">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-purple-100">{{ __('Total Products') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $stats['total_products'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-orange-100 dark:border-orange-900 bg-gradient-to-br from-orange-500 to-orange-600 p-5 text-white shadow-lg shadow-orange-500/30">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-orange-100">{{ __('Low Stock Items') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $stats['low_stock_count'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
    </div>
</div>
