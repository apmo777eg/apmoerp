<div class="lw-component">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-slate-800">{{ __('Quick Stats') }}</h2>
        <button 
            wire:click="refreshData" 
            class="text-sm text-emerald-600 hover:text-emerald-700 font-medium inline-flex items-center gap-1"
        >
            <svg wire:loading.class="animate-spin" wire:target="refreshData" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ __('Refresh') }}
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($widgets as $widget)
            @if($widget['visible'])
                @php
                    $widgetId = $widget['id'];
                    $title = $widget['title'];
                    
                    [$value, $icon, $color, $prefix, $suffix] = match($widgetId) {
                        'sales_today' => [
                            number_format($widgetData['total_sales_today'] ?? 0, 2),
                            'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                            'emerald',
                            '',
                            ' ' . __('EGP')
                        ],
                        'revenue_month' => [
                            number_format($widgetData['total_revenue_month'] ?? 0, 2),
                            'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                            'blue',
                            '',
                            ' ' . __('EGP')
                        ],
                        'total_products' => [
                            $widgetData['total_products'] ?? 0,
                            'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                            'purple',
                            '',
                            ''
                        ],
                        'total_customers' => [
                            $widgetData['total_customers'] ?? 0,
                            'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                            'rose',
                            '',
                            ''
                        ],
                        'low_stock' => [
                            $widgetData['low_stock_count'] ?? 0,
                            'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                            'orange',
                            '',
                            ''
                        ],
                        'pending_orders' => [
                            $widgetData['pending_orders'] ?? 0,
                            'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            'indigo',
                            '',
                            ''
                        ],
                        default => [0, 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'gray', '', '']
                    };
                @endphp

                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                    @php
                        $bgClass = match($color) {
                            'emerald' => 'bg-emerald-100',
                            'blue' => 'bg-blue-100',
                            'purple' => 'bg-purple-100',
                            'rose' => 'bg-rose-100',
                            'orange' => 'bg-orange-100',
                            'indigo' => 'bg-indigo-100',
                            default => 'bg-gray-100'
                        };
                        $textClass = match($color) {
                            'emerald' => 'text-emerald-600',
                            'blue' => 'text-blue-600',
                            'purple' => 'text-purple-600',
                            'rose' => 'text-rose-600',
                            'orange' => 'text-orange-600',
                            'indigo' => 'text-indigo-600',
                            default => 'text-gray-600'
                        };
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm text-slate-600 font-medium">{{ $title }}</p>
                            <p class="text-2xl font-bold text-slate-900 mt-1">
                                {{ $prefix }}{{ $value }}{{ $suffix }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-full {{ $bgClass }} flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 {{ $textClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                            </svg>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
