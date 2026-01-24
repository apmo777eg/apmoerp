<div class="space-y-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-lg md:text-xl font-semibold text-slate-800">
                {{ __('Store orders dashboard') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Analyze store orders, linked POS sales, and sources like Shopify or WooCommerce.') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] text-slate-500 mb-1">{{ __('Total orders') }}</p>
            <p class="text-xl font-semibold text-slate-800">{{ number_format($totalOrders) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] text-slate-500 mb-1">{{ __('Total revenue') }}</p>
            <p class="text-xl font-semibold text-emerald-600">{{ number_format($totalRevenue, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] text-slate-500 mb-1">{{ __('Total discount') }}</p>
            <p class="text-xl font-semibold text-amber-600">{{ number_format($totalDiscount, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] text-slate-500 mb-1">{{ __('Shipping + Tax') }}</p>
            <p class="text-xl font-semibold text-slate-800">
                {{ number_format($totalShipping + $totalTax, 2) }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
        <div class="xl:col-span-3 space-y-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs md:text-sm">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                            {{ __('Status') }}
                        </label>
                        <select wire:model="statusFilter"
                                class="w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs">
                            <option value="">{{ __('All') }}</option>
                            @foreach($allStatuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                            {{ __('Source') }}
                        </label>
                        <select wire:model="sourceFilter"
                                class="w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs">
                            <option value="">{{ __('All') }}</option>
                            @foreach($allSources as $src)
                                <option value="{{ $src }}">{{ ucfirst($src) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                            {{ __('From date') }}
                        </label>
                        <input type="date" wire:model="dateFrom"
                               class="w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                            {{ __('To date') }}
                        </label>
                        <input type="date" wire:model="dateTo"
                               class="w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-[11px] font-medium text-slate-600 mb-2">
                            {{ __('Revenue by source') }}
                        </p>
                        <canvas id="revenueBySourceChart" class="w-full h-40"></canvas>
                    </div>
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-[11px] font-medium text-slate-600 mb-2">
                            {{ __('Revenue by day') }}
                        </p>
                        <canvas id="ordersByDayChart" class="w-full h-40"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">
                    {{ __('Orders list') }}
                </h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs md:text-sm">
                        <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-[11px] font-medium text-slate-500">#</th>
                            <th class="px-3 py-2 text-left text-[11px] font-medium text-slate-500">{{ __('External ID') }}</th>
                            <th class="px-3 py-2 text-left text-[11px] font-medium text-slate-500">{{ __('Source') }}</th>
                            <th class="px-3 py-2 text-left text-[11px] font-medium text-slate-500">{{ __('Status') }}</th>
                            <th class="px-3 py-2 text-right text-[11px] font-medium text-slate-500">{{ __('Total') }}</th>
                            <th class="px-3 py-2 text-right text-[11px] font-medium text-slate-500">{{ __('Discount') }}</th>
                            <th class="px-3 py-2 text-right text-[11px] font-medium text-slate-500">{{ __('Shipping') }}</th>
                            <th class="px-3 py-2 text-right text-[11px] font-medium text-slate-500">{{ __('Tax') }}</th>
                            <th class="px-3 py-2 text-left text-[11px] font-medium text-slate-500">{{ __('Linked sale') }}</th>
                            <th class="px-3 py-2 text-left text-[11px] font-medium text-slate-500">{{ __('Created') }}</th>
                            <th class="px-3 py-2 text-right text-[11px] font-medium text-slate-500">{{ __('Details') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                        @forelse($orders as $order)
                            @php
                                $payload = $order->payload ?? [];
                                $source  = $order->source ?? data_get($payload, 'meta.source', 'unknown');
                                $saleId  = $order->sale->id ?? null;
                            @endphp
                            <tr>
                                <td class="px-3 py-1.5 text-[11px] text-slate-600">{{ $order->id }}</td>
                                <td class="px-3 py-1.5 text-[11px] text-slate-800">{{ $order->external_order_id }}</td>
                                <td class="px-3 py-1.5 text-[11px] text-slate-700">{{ ucfirst($source) }}</td>
                                <td class="px-3 py-1.5 text-[11px]">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                        @if($order->status === 'completed') bg-emerald-50 text-emerald-700 border-emerald-200
                                        @elseif($order->status === 'processed') bg-sky-50 text-sky-700 border-sky-200
                                        @else bg-amber-50 text-amber-700 border-amber-200 @endif">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-1.5 text-right text-[11px] text-slate-800">{{ number_format($order->total, 2) }}</td>
                                <td class="px-3 py-1.5 text-right text-[11px] text-slate-700">{{ number_format($order->discount_total, 2) }}</td>
                                <td class="px-3 py-1.5 text-right text-[11px] text-slate-700">{{ number_format($order->shipping_total, 2) }}</td>
                                <td class="px-3 py-1.5 text-right text-[11px] text-slate-700">{{ number_format($order->tax_total, 2) }}</td>
                                <td class="px-3 py-1.5 text-[11px] text-slate-700">
                                    @if($saleId)
                                        {{ __('Sale #') }}{{ $saleId }}
                                    @else
                                        <span class="text-slate-400">{{ __('N/A') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-1.5 text-[11px] text-slate-500">
                                    {{ optional($order->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-3 py-1.5 text-right">
                                    <button type="button" wire:click="viewOrder({{ $order->id }})"
                                            class="text-[11px] text-indigo-600 hover:text-indigo-700">
                                        {{ __('View') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-3 py-3 text-center text-xs text-slate-500">
                                    {{ __('No store orders found for the selected filters.') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>

        <div class="space-y-3">

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">
                    {{ __('Sources breakdown') }}
                </h2>
                <div class="space-y-2 text-[11px]">
                    @forelse($sources as $s)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-slate-800">
                                    {{ ucfirst($s['source']) }}
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    {{ __('Orders:') }} {{ $s['count'] }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-slate-800">
                                    {{ number_format($s['revenue'], 2) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-500">
                            {{ __('No orders yet.') }}
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">
                    {{ __('Export') }}
                </h2>
                <form method="GET" action="{{ route('admin.stores.orders.export') }}" class="space-y-3 text-[11px]">
                    <input type="hidden" name="from" value="{{ $dateFrom }}">
                    <input type="hidden" name="to" value="{{ $dateTo }}">
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                    <input type="hidden" name="source" value="{{ $sourceFilter }}">

                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                            {{ __('Format') }}
                        </label>
                        <select name="format"
                                class="w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs">
                            <option value="excel">{{ __('Excel (CSV)') }}</option>
                            <option value="pdf">{{ __('PDF') }}</option>
                            <option value="web">{{ __('Web view') }}</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[11px] font-medium text-slate-500">
                            {{ __('Columns') }}
                        </p>
                        @php
                            $defaultCols = [
                                'external_order_id' => __('External ID'),
                                'source'            => __('Source'),
                                'status'            => __('Status'),
                                'total'             => __('Total'),
                                'discount_total'    => __('Discount'),
                                'shipping_total'    => __('Shipping'),
                                'tax_total'         => __('Tax'),
                                'created_at'        => __('Created at'),
                            ];
                        @endphp
                        @foreach($defaultCols as $colKey => $colLabel)
                            <label class="flex items-center gap-2 text-[11px] text-slate-600">
                                <input type="checkbox" name="columns[]" value="{{ $colKey }}" checked
                                       class="rounded border-slate-300">
                                <span>{{ $colLabel }}</span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-lg border border-indigo-500 bg-indigo-500 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-600">
                        {{ __('Export now') }}
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">
                    {{ __('Order details') }}
                </h2>
                @if($selectedOrderDetails)
                    <div class="space-y-2 text-[11px] text-slate-700">
                        <div>
                            <p class="font-medium text-slate-800">
                                {{ __('External ID') }}: {{ $selectedOrderDetails['external_order_id'] }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Status') }}: {{ ucfirst($selectedOrderDetails['status']) }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Source') }}: {{ ucfirst($selectedOrderDetails['source']) }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Total') }}: {{ number_format($selectedOrderDetails['total'], 2) }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Discount') }}: {{ number_format($selectedOrderDetails['discount_total'], 2) }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Shipping') }}: {{ number_format($selectedOrderDetails['shipping_total'], 2) }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Tax') }}: {{ number_format($selectedOrderDetails['tax_total'], 2) }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Created at') }}: {{ $selectedOrderDetails['created_at'] }}
                            </p>
                            <p class="text-slate-500">
                                {{ __('Linked sale') }}:
                                @if($selectedOrderDetails['sale_id'])
                                    {{ __('Sale #') }}{{ $selectedOrderDetails['sale_id'] }}
                                @else
                                    <span class="text-slate-400">{{ __('N/A') }}</span>
                                @endif
                            </p>
                        </div>

                        @if(!empty($selectedOrderDetails['customer']))
                            <div>
                                <p class="font-medium text-slate-800 mb-1">
                                    {{ __('Customer') }}
                                </p>
                                <pre class="text-[10px] bg-slate-50 rounded p-2 overflow-x-auto">{{ json_encode($selectedOrderDetails['customer'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif

                        @if(!empty($selectedOrderDetails['items']))
                            <div class="space-y-1">
                                <p class="font-medium text-slate-800">
                                    {{ __('Items') }}
                                </p>
                                <div class="max-h-48 overflow-y-auto border border-slate-100 rounded">
                                    <table class="min-w-full text-[10px]">
                                        <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-2 py-1 text-left">{{ __('SKU') }}</th>
                                            <th class="px-2 py-1 text-left">{{ __('Name') }}</th>
                                            <th class="px-2 py-1 text-right">{{ __('Qty') }}</th>
                                            <th class="px-2 py-1 text-right">{{ __('Price') }}</th>
                                            <th class="px-2 py-1 text-right">{{ __('Total') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50">
                                        @foreach($selectedOrderDetails['items'] as $item)
                                            <tr>
                                                <td class="px-2 py-1">{{ $item['sku'] ?? '' }}</td>
                                                <td class="px-2 py-1">{{ $item['name'] ?? '' }}</td>
                                                <td class="px-2 py-1 text-right">{{ $item['qty'] ?? '' }}</td>
                                                <td class="px-2 py-1 text-right">{{ $item['price'] ?? '' }}</td>
                                                <td class="px-2 py-1 text-right">{{ $item['total'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if(!empty($selectedOrderDetails['meta']))
                            <div>
                                <p class="font-medium text-slate-800 mb-1">
                                    {{ __('Meta') }}
                                </p>
                                <pre class="text-[10px] bg-slate-50 rounded p-2 overflow-x-auto">{{ json_encode($selectedOrderDetails['meta'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-[11px] text-slate-500">
                        {{ __('Select an order from the list to view details.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

@script
// UNFIXED-01 FIX: Use @script block for proper Livewire 4 component-scoped JavaScript
const componentId = 'orders-dashboard-' + ($wire.__instance?.id ?? Math.random().toString(36).substr(2, 9));

window.__lwCharts = window.__lwCharts || {};

// Destroy existing charts
['revenue', 'orders'].forEach(type => {
    if (window.__lwCharts[componentId + ':' + type]) {
        window.__lwCharts[componentId + ':' + type].destroy();
        delete window.__lwCharts[componentId + ':' + type];
    }
});

const revenueCtx = document.getElementById('revenueBySourceChart')?.getContext('2d');
const ordersCtx = document.getElementById('ordersByDayChart')?.getContext('2d');

function initStoreOrdersCharts(data = {}) {
    if (!revenueCtx || !ordersCtx) return;
    
    const bySource = data.revenueBySource || {labels: [], values: []};
    const byDay = data.ordersByDay || {labels: [], values: []};

    // Destroy existing
    if (window.__lwCharts[componentId + ':revenue']) {
        window.__lwCharts[componentId + ':revenue'].destroy();
    }
    if (window.__lwCharts[componentId + ':orders']) {
        window.__lwCharts[componentId + ':orders'].destroy();
    }

    window.__lwCharts[componentId + ':revenue'] = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: bySource.labels,
            datasets: [{
                label: '{{ __('Revenue') }}',
                data: bySource.values,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: { ticks: { font: { size: 10 } } },
            },
        },
    });

    window.__lwCharts[componentId + ':orders'] = new Chart(ordersCtx, {
        type: 'line',
        data: {
            labels: byDay.labels,
            datasets: [{
                label: '{{ __('Revenue') }}',
                data: byDay.values,
                tension: 0.3,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: { ticks: { font: { size: 10 } } },
            },
        },
    });
}

// Load Chart.js if not already loaded
if (typeof Chart === 'undefined') {
    const scriptEl = document.createElement('script');
    scriptEl.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    scriptEl.onload = () => initStoreOrdersCharts();
    document.head.appendChild(scriptEl);
} else {
    initStoreOrdersCharts();
}

// Listen for chart updates from Livewire
$wire.on('store-orders-charts-update', (payload) => {
    const data = payload.chartData || payload || {};
    initStoreOrdersCharts(data);
});

// Clean up when navigating away
document.addEventListener('livewire:navigating', () => {
    ['revenue', 'orders'].forEach(type => {
        if (window.__lwCharts[componentId + ':' + type]) {
            window.__lwCharts[componentId + ':' + type].destroy();
            delete window.__lwCharts[componentId + ':' + type];
        }
    });
}, { once: true });
@endscript
</div>
