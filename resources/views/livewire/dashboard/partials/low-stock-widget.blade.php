<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">{{ __('Low Stock Alerts') }}</h3>
        @can('inventory.products.view')
        <a href="{{ route('app.inventory.products.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700">{{ __('View All') }}</a>
        @endcan
    </div>
    @if(count($lowStockProducts) > 0)
        <div class="space-y-3">
            @foreach($lowStockProducts as $product)
                <div class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100 dark:border-orange-800">
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">{{ $product['name'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $product['category'] }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $product['quantity'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Min') }}: {{ $product['min_stock'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-slate-400 dark:text-slate-500">
            <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm">{{ __('All products in stock') }}</p>
        </div>
    @endif
</div>
