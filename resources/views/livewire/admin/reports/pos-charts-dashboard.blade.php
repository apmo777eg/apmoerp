<div class="space-y-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-lg md:text-xl font-semibold text-slate-800">
                {{ __('POS charts dashboard') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Visualize POS sales performance by day and by branch.') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] text-slate-500 mb-1">{{ __('Total sales count') }}</p>
            <p class="text-xl font-semibold text-slate-800">{{ number_format($totalSales) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] text-slate-500 mb-1">{{ __('Total revenue') }}</p>
            <p class="text-xl font-semibold text-emerald-600">{{ number_format($totalRevenue, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] text-slate-500 mb-1">{{ __('Average per day') }}</p>
            <p class="text-xl font-semibold text-slate-800">
                @php
                    $days = max(1, count($__data['chartData']['salesByDay']['labels'] ?? []));
                    $avg = $days ? $totalRevenue / $days : 0;
                @endphp
                {{ number_format($avg ?? 0, 2) }}
            </p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs md:text-sm">
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
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                    {{ __('Branch ID') }}
                </label>
                <input type="number" wire:model="branchId"
                       class="w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs">
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-100 p-3">
                <p class="text-[11px] font-medium text-slate-600 mb-2">
                    {{ __('Sales by day') }}
                </p>
                <canvas id="posSalesByDayChart" class="w-full h-40"></canvas>
            </div>
            <div class="rounded-xl border border-slate-100 p-3">
                <p class="text-[11px] font-medium text-slate-600 mb-2">
                    {{ __('Sales by branch') }}
                </p>
                <canvas id="posSalesByBranchChart" class="w-full h-40"></canvas>
            </div>
        </div>
    </div>

@script
// UNFIXED-01 FIX: Use @script block for proper Livewire 4 component-scoped JavaScript
const componentId = 'pos-charts-' + ($wire.__instance?.id ?? Math.random().toString(36).substr(2, 9));

window.__lwCharts = window.__lwCharts || {};

// Destroy existing charts
['byDay', 'byBranch'].forEach(type => {
    if (window.__lwCharts[componentId + ':' + type]) {
        window.__lwCharts[componentId + ':' + type].destroy();
        delete window.__lwCharts[componentId + ':' + type];
    }
});

const dayCtx = document.getElementById('posSalesByDayChart')?.getContext('2d');
const branchCtx = document.getElementById('posSalesByBranchChart')?.getContext('2d');

function initPosCharts(data = {}) {
    if (!dayCtx || !branchCtx) return;
    
    const byDay = data.salesByDay || {labels: [], values: []};
    const byBranch = data.salesByBranch || {labels: [], values: []};

    // Destroy existing
    if (window.__lwCharts[componentId + ':byDay']) {
        window.__lwCharts[componentId + ':byDay'].destroy();
    }
    if (window.__lwCharts[componentId + ':byBranch']) {
        window.__lwCharts[componentId + ':byBranch'].destroy();
    }

    window.__lwCharts[componentId + ':byDay'] = new Chart(dayCtx, {
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

    window.__lwCharts[componentId + ':byBranch'] = new Chart(branchCtx, {
        type: 'bar',
        data: {
            labels: byBranch.labels,
            datasets: [{
                label: '{{ __('Revenue') }}',
                data: byBranch.values,
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
    scriptEl.onload = () => initPosCharts();
    document.head.appendChild(scriptEl);
} else {
    initPosCharts();
}

// Listen for chart updates from Livewire
$wire.on('pos-charts-update', (payload) => {
    const data = payload.chartData || payload || {};
    initPosCharts(data);
});

// Clean up when navigating away
document.addEventListener('livewire:navigating', () => {
    ['byDay', 'byBranch'].forEach(type => {
        if (window.__lwCharts[componentId + ':' + type]) {
            window.__lwCharts[componentId + ':' + type].destroy();
            delete window.__lwCharts[componentId + ':' + type];
        }
    });
}, { once: true });
@endscript
</div>
