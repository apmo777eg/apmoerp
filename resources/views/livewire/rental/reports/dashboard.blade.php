<div class="space-y-6">
    @include('components.erp.breadcrumb', [
        'items' => [
            ['label' => __('Rental')],
            ['label' => __('Reports')],
        ],
    ])

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-50">
                {{ __('Rental Reports') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Units occupancy and expiring contracts overview.') }}
            </p>
        </div>
        <div class="flex items-center space-x-3">
            {{-- LOW-002 FIX: Export functionality not yet implemented - buttons removed --}}
            {{-- Export routes need to be defined in routes/web.php before enabling:
                 - Route::get('rental/reports/occupancy/export', [OccupancyReportController::class, 'export'])->name('app.rental.reports.occupancy.export');
                 - Route::get('rental/reports/contracts/expiring/export', [ExpiringContractsController::class, 'export'])->name('app.rental.reports.contracts.expiring.export');
            --}}
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ __('Units summary') }}
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ $unitsSummary['total'] ?? 0 }} {{ __('units') }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Occupied') }}:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $unitsSummary['occupied'] ?? 0 }}
                </span>
                · {{ __('Vacant') }}:
                <span class="font-semibold text-slate-700 dark:text-slate-200">
                    {{ $unitsSummary['vacant'] ?? 0 }}
                </span>
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Occupancy rate') }}:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $unitsSummary['occupancy_rate'] ?? 0 }}%
                </span>
            </p>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ __('Active contracts') }}
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ $contractsSummary['active'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Expiring in next') }}
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $contractsSummary['window_days'] ?? 30 }} {{ __('days') }}
                </span>:
                <span class="font-semibold">
                    {{ $contractsSummary['expiring_soon'] ?? 0 }}
                </span>
            </p>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ __('Filters') }}
            </h3>
            <div class="mt-3 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('Expiring contracts window (days)') }}
                    </label>
                    <select wire:model="filters.expiring_in_days" class="erp-input">
                        <option value="7">{{ __('7 days') }}</option>
                        <option value="15">{{ __('15 days') }}</option>
                        <option value="30">{{ __('30 days') }}</option>
                        <option value="60">{{ __('60 days') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="erp-card p-4 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    {{ __('Units by status') }}
                </h2>
            </div>
            <div wire:ignore>
                <canvas id="rentalUnitsChart" class="w-full h-48"></canvas>
            </div>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    {{ __('Active contracts by end date') }}
                </h2>
            </div>
            <div wire:ignore>
                <canvas id="rentalContractsChart" class="w-full h-48"></canvas>
            </div>
        </div>
    </div>

    <div class="erp-card p-4 rounded-2xl">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50 mb-3">
            {{ __('Contracts expiring soon') }}
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200/80 dark:border-slate-700/80 text-left text-xs uppercase text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="py-2 pr-4">{{ __('Property') }}</th>
                        <th class="py-2 pr-4">{{ __('Unit') }}</th>
                        <th class="py-2 pr-4">{{ __('Tenant') }}</th>
                        <th class="py-2 pr-4">{{ __('End date') }}</th>
                        <th class="py-2 pr-4">{{ __('Rent') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200">
                    @php
                        $contractModel = '\\App\\Models\\RentalContract';
                        $days = $filters['expiring_in_days'] ?? 30;
                        $threshold = now()->addDays($days)->toDateString();
                        $contracts = class_exists($contractModel)
                            ? $contractModel::with(['unit.property', 'tenant'])
                                ->where('status', 'active')
                                ->whereDate('end_date', '<=', $threshold)
                                ->orderBy('end_date')
                                ->limit(10)
                                ->get()
                            : collect();
                    @endphp
                    @forelse($contracts as $row)
                        <tr class="erp-pos-row">
                            <td class="py-2 pr-4">
                                {{ optional(optional($row->unit)->property)->name ?? '—' }}
                            </td>
                            <td class="py-2 pr-4">
                                {{ optional($row->unit)->code ?? '—' }}
                            </td>
                            <td class="py-2 pr-4">
                                {{ optional($row->tenant)->name ?? '—' }}
                            </td>
                            <td class="py-2 pr-4">
                                {{ $row->end_date }}
                            </td>
                            <td class="py-2 pr-4">
                                {{ number_format($row->rent ?? 0, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ __('No expiring contracts found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@script
    // NEW-02/UNFIXED-01 FIX: Use @script block to prevent duplicate handler registration
    const componentId = 'rental-dashboard-' + ($wire.__instance?.id ?? Math.random().toString(36).substr(2, 9));
    
    // Initialize global chart storage if not exists
    window.__lwCharts = window.__lwCharts || {};
    
    // Destroy any existing charts for this component
    ['units', 'contracts'].forEach(type => {
        if (window.__lwCharts[componentId + ':' + type]) {
            window.__lwCharts[componentId + ':' + type].destroy();
            delete window.__lwCharts[componentId + ':' + type];
        }
    });
    
    function initRentalCharts() {
        const unitsCtx = document.getElementById('rentalUnitsChart')?.getContext('2d');
        const contractsCtx = document.getElementById('rentalContractsChart')?.getContext('2d');
        
        const unitsData = @json($unitsChart);
        const contractsData = @json($contractsChart);
        
        if (unitsCtx && unitsData.labels && unitsData.labels.length) {
            window.__lwCharts[componentId + ':units'] = new Chart(unitsCtx, {
                type: 'doughnut',
                data: {
                    labels: unitsData.labels,
                    datasets: [{
                        data: unitsData.data,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
        
        if (contractsCtx && contractsData.labels && contractsData.labels.length) {
            window.__lwCharts[componentId + ':contracts'] = new Chart(contractsCtx, {
                type: 'bar',
                data: {
                    labels: contractsData.labels,
                    datasets: [{
                        data: contractsData.data,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    }
    
    // Load Chart.js if not already loaded, then initialize
    if (typeof Chart === 'undefined') {
        const scriptEl = document.createElement('script');
        scriptEl.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        scriptEl.onload = initRentalCharts;
        document.head.appendChild(scriptEl);
    } else {
        initRentalCharts();
    }
    
    // Clean up when navigating away
    document.addEventListener('livewire:navigating', () => {
        ['units', 'contracts'].forEach(type => {
            if (window.__lwCharts[componentId + ':' + type]) {
                window.__lwCharts[componentId + ':' + type].destroy();
                delete window.__lwCharts[componentId + ':' + type];
            }
        });
    }, { once: true });
@endscript
