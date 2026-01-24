{{-- resources/views/livewire/dashboard/index.blade.php --}}
<div class="space-y-6">
    {{-- Loading Overlay --}}
    <div wire:loading wire:target="refreshData" class="loading-overlay flex items-center justify-center bg-slate-900/50">
        <div class="bg-white rounded-2xl p-6 shadow-xl flex flex-col items-center gap-4">
            <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-slate-600 font-medium">{{ __('Refreshing data...') }}</p>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-bold text-slate-800">
                {{ __('Business ERP Solution') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Quick overview of your business today.') }}
            </p>
        </div>
        <button 
            wire:click="refreshData" 
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg wire:loading.class="animate-spin" wire:target="refreshData" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span wire:loading.remove wire:target="refreshData">{{ __('Refresh') }}</span>
            <span wire:loading wire:target="refreshData">{{ __('Loading...') }}</span>
        </button>
    </div>

    {{-- Role-aware quick actions --}}
    <x-dashboard.quick-actions />

    {{-- Quick Access Buttons Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">

        {{-- New Sale --}}
        <a href="{{ route('pos.terminal') }}" class="group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-pink-200 transition-all duration-300">
            <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-pink-100 to-pink-50 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-700 text-center">{{ __('New Sale') }}</span>
        </a>

        {{-- Sales Report --}}
        <a href="{{ route('admin.reports.index') }}" class="group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-blue-200 transition-all duration-300">
            <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-50 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-700 text-center">{{ __('Sales Report') }}</span>
        </a>

        {{-- Inventory Management --}}
        <a href="{{ route('app.inventory.products.index') }}" class="group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-green-200 transition-all duration-300">
            <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-green-100 to-green-50 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-700 text-center">{{ __('Inventory') }}</span>
        </a>

        {{-- Employees --}}
        <a href="{{ route('app.hrm.employees.index') }}" class="group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-rose-200 transition-all duration-300">
            <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-rose-100 to-rose-50 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-700 text-center">{{ __('Employees') }}</span>
        </a>

        {{-- Settings --}}
        <a href="{{ route('admin.settings') }}" class="group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-gray-200 transition-all duration-300">
            <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-gray-100 to-gray-50 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-700 text-center">{{ __('Settings') }}</span>
        </a>

        {{-- Advanced Settings --}}
        <a href="{{ route('admin.settings', ['tab' => 'advanced']) }}" class="group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-rose-200 transition-all duration-300">
            <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-rose-100 to-rose-50 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-700 text-center">{{ __('Security') }}</span>
        </a>

    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 text-white shadow-lg shadow-emerald-500/30">
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

        <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-500 to-blue-600 p-5 text-white shadow-lg shadow-blue-500/30">
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

        <div class="rounded-2xl border border-purple-100 bg-gradient-to-br from-purple-500 to-purple-600 p-5 text-white shadow-lg shadow-purple-500/30">
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

        <div class="rounded-2xl border border-orange-100 bg-gradient-to-br from-orange-500 to-orange-600 p-5 text-white shadow-lg shadow-orange-500/30">
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

    {{-- Performance insights --}}
    <div class="grid gap-4 lg:grid-cols-3">
        @php
            $weeklyChange = $trendIndicators['weekly_sales']['change'] ?? 0;
            $weeklyDirectionClass = $weeklyChange >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
        @endphp
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500">{{ __('Weekly sales change') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">{{ $weeklyChange }}%</p>
                    <p class="text-xs text-slate-500 mt-1">
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
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">{{ __('Inventory health') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">{{ $inventoryHealth }}%</p>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Based on low stock alerts vs total items.') }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-semibold">
                    {{ max(0, min(99, $inventoryHealth)) }}%
                </div>
            </div>
            <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600" style="width: {{ max(0, min(100, $inventoryHealth)) }}%"></div>
            </div>
        </div>

        @php
            $invoiceClearRate = $trendIndicators['invoice_clear_rate'] ?? 0;
            $clearClass = $invoiceClearRate >= 80 ? 'bg-emerald-50 text-emerald-700' : ($invoiceClearRate >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700');
        @endphp
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500">{{ __('Invoice clearance') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">{{ $invoiceClearRate }}%</p>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Completed invoices vs total recorded.') }}</p>
                </div>
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $clearClass }}">
                    {{ $invoiceClearRate >= 80 ? __('Healthy') : ($invoiceClearRate >= 50 ? __('Needs attention') : __('Action required')) }}
                </span>
            </div>
            <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-400 to-sky-500" style="width: {{ max(0, min(100, $invoiceClearRate)) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Sales Chart --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm xl:col-span-2">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Sales Trend (Last 7 Days)') }}</h3>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Inventory Status Chart --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Inventory Status') }}</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="inventoryChart"></canvas>
                </div>
            </div>

            {{-- Payment methods breakdown --}}
            @php
                $totalPayments = array_sum($paymentMethodsData['data'] ?? []);
                $totalPaymentAmount = array_sum($paymentMethodsData['totals'] ?? []);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-slate-800">{{ __('Payment mix (This month)') }}</h3>
                    <span class="text-xs text-slate-500">{{ __('Total') }}: {{ number_format($totalPaymentAmount ?? 0, 2) }} {{ __('EGP') }}</span>
                </div>
                @if($totalPayments > 0)
                    <div class="space-y-4">
                        @foreach($paymentMethodsData['labels'] ?? [] as $index => $method)
                            @php
                                $methodCount = $paymentMethodsData['data'][$index] ?? 0;
                                $methodTotal = $paymentMethodsData['totals'][$index] ?? 0;
                                $percent = $totalPayments > 0 ? round(($methodCount / $totalPayments) * 100) : 0;
                            @endphp
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm font-medium text-slate-700">
                                    <span>{{ $method }}</span>
                                    <span class="text-slate-500">{{ $percent }}% · {{ $methodCount }} {{ __('payments') }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600" style="width: {{ $percent }}%"></div>
                                </div>
                                <p class="text-xs text-slate-500">{{ __('Amount') }}: {{ number_format($methodTotal, 2) }} {{ __('EGP') }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">{{ __('No payments recorded for this period.') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Low Stock & Recent Sales Row --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Low Stock Products --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800">{{ __('Low Stock Alerts') }}</h3>
                <a href="{{ route('app.inventory.products.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700">{{ __('View All') }}</a>
            </div>
            @if(count($lowStockProducts) > 0)
                <div class="space-y-3">
                    @foreach($lowStockProducts as $product)
                        <div class="flex items-center justify-between p-3 bg-orange-50 rounded-xl border border-orange-100">
                            <div>
                                <p class="font-medium text-slate-800">{{ $product['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $product['category'] }}</p>
                            </div>
                            <div class="text-end">
                                <p class="text-lg font-bold text-orange-600">{{ $product['quantity'] }}</p>
                                <p class="text-xs text-slate-500">{{ __('Min') }}: {{ $product['min_stock'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">{{ __('All products in stock') }}</p>
                </div>
            @endif
        </div>

        {{-- Recent Sales --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800">{{ __('Recent Sales') }}</h3>
                <a href="{{ route('admin.reports.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700">{{ __('View All') }}</a>
            </div>
            @if(count($recentSales) > 0)
                <div class="space-y-3">
                    @foreach($recentSales as $sale)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                            <div>
                                <p class="font-medium text-slate-800">{{ $sale['reference'] }}</p>
                                <p class="text-xs text-slate-500">{{ $sale['customer'] }} - {{ $sale['date'] }}</p>
                            </div>
                            <div class="text-end">
                                <p class="text-lg font-bold text-emerald-600">{{ $sale['total'] }} {{ __('EGP') }}</p>
                                <span class="text-xs px-2 py-0.5 rounded-full 
                                    @if($sale['status'] === 'completed') bg-green-100 text-green-700
                                    @elseif($sale['status'] === 'pending') bg-yellow-100 text-yellow-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($sale['status']) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm">{{ __('No recent sales') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Stats Footer --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['active_branches'] ?? 0 }}</p>
                <p class="text-sm text-slate-500">{{ __('Active Branches') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['active_users'] ?? 0 }}</p>
                <p class="text-sm text-slate-500">{{ __('Active Users') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['open_invoices'] ?? 0 }}</p>
                <p class="text-sm text-slate-500">{{ __('Open Invoices') }}</p>
            </div>
        </div>
    </div>
</div>

@script
// UNFIXED-01 FIX: Use @script block for proper Livewire 4 component-scoped JavaScript
const componentId = 'dashboard-index-' + ($wire.__instance?.id ?? Math.random().toString(36).substr(2, 9));

// Initialize global chart storage if not exists
window.__lwCharts = window.__lwCharts || {};

// Destroy any existing charts for this component
['sales', 'inventory'].forEach(type => {
    if (window.__lwCharts[componentId + ':' + type]) {
        window.__lwCharts[componentId + ':' + type].destroy();
        delete window.__lwCharts[componentId + ':' + type];
    }
});

function initDashboardCharts() {
    const isRTL = document.documentElement.dir === 'rtl';
    
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        window.__lwCharts[componentId + ':sales'] = new Chart(salesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($salesChartData['labels'] ?? []),
                datasets: [{
                    label: '{{ __("Sales") }}',
                    data: @json($salesChartData['data'] ?? []),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rtl: isRTL,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    const inventoryCtx = document.getElementById('inventoryChart');
    if (inventoryCtx) {
        window.__lwCharts[componentId + ':inventory'] = new Chart(inventoryCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['{{ __("In Stock") }}', '{{ __("Low Stock") }}', '{{ __("Out of Stock") }}'],
                datasets: [{
                    data: @json($inventoryChartData['data'] ?? [0, 0, 0]),
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rtl: isRTL,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: isRTL,
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
}

// Load Chart.js if not already loaded, then initialize
if (typeof Chart === 'undefined') {
    const scriptEl = document.createElement('script');
    scriptEl.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    scriptEl.onload = initDashboardCharts;
    document.head.appendChild(scriptEl);
} else {
    initDashboardCharts();
}

// Clean up when navigating away
document.addEventListener('livewire:navigating', () => {
    ['sales', 'inventory'].forEach(type => {
        if (window.__lwCharts[componentId + ':' + type]) {
            window.__lwCharts[componentId + ':' + type].destroy();
            delete window.__lwCharts[componentId + ':' + type];
        }
    });
}, { once: true });
@endscript
