<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">{{ __('Sales Analytics') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Comprehensive sales performance insights') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select wire:model.live="dateRange" class="erp-input text-sm w-32">
                <option value="today">{{ __('Today') }}</option>
                <option value="week">{{ __('This Week') }}</option>
                <option value="month">{{ __('This Month') }}</option>
                <option value="quarter">{{ __('This Quarter') }}</option>
                <option value="year">{{ __('This Year') }}</option>
                <option value="custom">{{ __('Custom') }}</option>
            </select>
            @if($dateRange === 'custom')
            <input type="date" wire:model.live="dateFrom" class="erp-input text-sm w-36">
            <span class="text-slate-400">-</span>
            <input type="date" wire:model.live="dateTo" class="erp-input text-sm w-36">
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="erp-card p-4">
            <p class="text-xs text-slate-500 mb-1">{{ __('Total Revenue') }}</p>
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($summaryStats['total_sales'] ?? 0, 2) }}</p>
            <p class="text-xs mt-1 {{ ($summaryStats['sales_growth'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                @if(($summaryStats['sales_growth'] ?? 0) >= 0)
                    <span>+</span>
                @endif
                {{ $summaryStats['sales_growth'] ?? 0 }}% {{ __('vs previous period') }}
            </p>
        </div>
        <div class="erp-card p-4">
            <p class="text-xs text-slate-500 mb-1">{{ __('Total Orders') }}</p>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($summaryStats['total_orders'] ?? 0) }}</p>
            <p class="text-xs text-slate-500 mt-1">
                {{ $summaryStats['completion_rate'] ?? 0 }}% {{ __('completed') }}
            </p>
        </div>
        <div class="erp-card p-4">
            <p class="text-xs text-slate-500 mb-1">{{ __('Avg Order Value') }}</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($summaryStats['avg_order_value'] ?? 0, 2) }}</p>
        </div>
        <div class="erp-card p-4">
            <p class="text-xs text-slate-500 mb-1">{{ __('Total Discount') }}</p>
            <p class="text-2xl font-bold text-amber-600">{{ number_format($summaryStats['total_discount'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="erp-card p-4">
            <h3 class="font-medium text-slate-800 mb-4">{{ __('Sales Trend') }}</h3>
            <div class="h-64">
                <canvas id="salesTrendChart" wire:ignore></canvas>
            </div>
        </div>

        <div class="erp-card p-4">
            <h3 class="font-medium text-slate-800 mb-4">{{ __('Payment Methods') }}</h3>
            <div class="h-64">
                <canvas id="paymentChart" wire:ignore></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="erp-card p-4">
            <h3 class="font-medium text-slate-800 mb-4">{{ __('Top 10 Products') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-start py-2 px-2 text-xs text-slate-500">{{ __('Product') }}</th>
                            <th class="text-end py-2 px-2 text-xs text-slate-500">{{ __('Qty') }}</th>
                            <th class="text-end py-2 px-2 text-xs text-slate-500">{{ __('Revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $product)
                        <tr class="border-b border-slate-100 hover:bg-emerald-50/30">
                            <td class="py-2 px-2">
                                <p class="font-medium text-slate-800">{{ $product['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $product['sku'] }}</p>
                            </td>
                            <td class="text-end py-2 px-2 text-slate-600">{{ number_format($product['quantity']) }}</td>
                            <td class="text-end py-2 px-2 font-medium text-emerald-600">{{ number_format($product['revenue'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-400">{{ __('No data available') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="erp-card p-4">
            <h3 class="font-medium text-slate-800 mb-4">{{ __('Top 10 Customers') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-start py-2 px-2 text-xs text-slate-500">{{ __('Customer') }}</th>
                            <th class="text-end py-2 px-2 text-xs text-slate-500">{{ __('Orders') }}</th>
                            <th class="text-end py-2 px-2 text-xs text-slate-500">{{ __('Total Spent') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCustomers as $customer)
                        <tr class="border-b border-slate-100 hover:bg-emerald-50/30">
                            <td class="py-2 px-2">
                                <p class="font-medium text-slate-800">{{ $customer['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $customer['email'] ?? '-' }}</p>
                            </td>
                            <td class="text-end py-2 px-2 text-slate-600">{{ number_format($customer['orders']) }}</td>
                            <td class="text-end py-2 px-2 font-medium text-emerald-600">{{ number_format($customer['total_spent'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-400">{{ __('No data available') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="erp-card p-4">
            <h3 class="font-medium text-slate-800 mb-4">{{ __('Hourly Sales Distribution') }}</h3>
            <div class="h-48">
                <canvas id="hourlyChart" wire:ignore></canvas>
            </div>
        </div>

        <div class="erp-card p-4">
            <h3 class="font-medium text-slate-800 mb-4">{{ __('Category Performance') }}</h3>
            <div class="h-48">
                <canvas id="categoryChart" wire:ignore></canvas>
            </div>
        </div>
    </div>
</div>

@script
// UNFIXED-01 FIX: Use @script block for proper Livewire 4 component-scoped JavaScript
const componentId = 'sales-analytics-' + ($wire.__instance?.id ?? Math.random().toString(36).substr(2, 9));

// Initialize global chart storage if not exists
window.__lwCharts = window.__lwCharts || {};

// Destroy any existing charts for this component
['salesTrend', 'payment', 'hourly', 'category'].forEach(type => {
    if (window.__lwCharts[componentId + ':' + type]) {
        window.__lwCharts[componentId + ':' + type].destroy();
        delete window.__lwCharts[componentId + ':' + type];
    }
});

function initSalesAnalyticsCharts() {
    const salesTrendCtx = document.getElementById('salesTrendChart');
    if (salesTrendCtx) {
        window.__lwCharts[componentId + ':salesTrend'] = new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: @json($salesTrend['labels'] ?? []),
                datasets: [{
                    label: '{{ __("Revenue") }}',
                    data: @json($salesTrend['revenue'] ?? []),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const paymentCtx = document.getElementById('paymentChart');
    if (paymentCtx) {
        window.__lwCharts[componentId + ':payment'] = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: @json($paymentBreakdown['labels'] ?? []),
                datasets: [{
                    data: @json($paymentBreakdown['totals'] ?? []),
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    }

    const hourlyCtx = document.getElementById('hourlyChart');
    if (hourlyCtx) {
        window.__lwCharts[componentId + ':hourly'] = new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: @json($hourlyDistribution['labels'] ?? []),
                datasets: [{
                    label: '{{ __("Orders") }}',
                    data: @json($hourlyDistribution['data'] ?? []),
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        window.__lwCharts[componentId + ':category'] = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: @json($categoryPerformance['labels'] ?? []),
                datasets: [{
                    label: '{{ __("Revenue") }}',
                    data: @json($categoryPerformance['revenues'] ?? []),
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
}

// Load Chart.js if not already loaded, then initialize
if (typeof Chart === 'undefined') {
    const scriptEl = document.createElement('script');
    scriptEl.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    scriptEl.onload = initSalesAnalyticsCharts;
    document.head.appendChild(scriptEl);
} else {
    initSalesAnalyticsCharts();
}

// Clean up when navigating away
document.addEventListener('livewire:navigating', () => {
    ['salesTrend', 'payment', 'hourly', 'category'].forEach(type => {
        if (window.__lwCharts[componentId + ':' + type]) {
            window.__lwCharts[componentId + ':' + type].destroy();
            delete window.__lwCharts[componentId + ':' + type];
        }
    });
}, { once: true });
@endscript
