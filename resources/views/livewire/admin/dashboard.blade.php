<div class="space-y-6">
    @include('components.erp.breadcrumb', [
        'items' => [
            ['label' => 'System'],
            ['label' => 'Overview'],
        ],
    ])

    @php
        $today = now()->toDateString();
        $from = now()->subDays(14)->toDateString();

        $saleModel = '\\App\\Models\\Sale';
        $nonRevenueStatuses = \App\Enums\SaleStatus::nonRevenueStatuses();
        // V35-HIGH-02 FIX: Use sale_date instead of created_at for accurate financial reporting
        // V35-MED-06 FIX: Exclude non-revenue statuses using SaleStatus enum
        // SECURITY: The selectRaw below uses only hardcoded column/function names
        $salesStats = [
            'count' => class_exists($saleModel) ? $saleModel::count() : 0,
            'today' => class_exists($saleModel) ? $saleModel::whereDate('sale_date', $today)->whereNotIn('status', $nonRevenueStatuses)->sum('total_amount') : 0,
            'total' => class_exists($saleModel) ? $saleModel::whereNotIn('status', $nonRevenueStatuses)->sum('total_amount') : 0,
        ];

        $employeeModel = '\\App\\Models\\HREmployee';
        $attendanceModel = '\\App\\Models\\Attendance';
        // NEW-V49-CRITICAL-01 FIX: Use 'attendance_date' (actual DB column) instead of 'date' (accessor only)
        $hrStats = [
            'employees' => class_exists($employeeModel) ? $employeeModel::count() : 0,
            'today_attendance' => class_exists($attendanceModel) ? $attendanceModel::whereDate('attendance_date', $today)->count() : 0,
        ];

        $unitModel = '\\App\\Models\\RentalUnit';
        $contractModel = '\\App\\Models\\RentalContract';
        $rentalStats = [
            'units' => class_exists($unitModel) ? $unitModel::count() : 0,
            'active_contracts' => class_exists($contractModel) ? $contractModel::where('status', 'active')->count() : 0,
        ];

        $branchModel = '\\App\\Models\\Branch';
        $customerModel = '\\App\\Models\\Customer';
        $metaStats = [
            'branches' => class_exists($branchModel) ? $branchModel::count() : 0,
            'customers' => class_exists($customerModel) ? $customerModel::count() : 0,
        ];

        // V35-HIGH-02 FIX: Use sale_date instead of created_at
        // V35-MED-06 FIX: Exclude non-revenue statuses using SaleStatus enum
        // SECURITY: The selectRaw uses only hardcoded column/function names (DATE, SUM)
        $salesSeries = class_exists($saleModel)
            ? $saleModel::selectRaw('DATE(sale_date) as day, SUM(total_amount) as total')
                ->whereDate('sale_date', '>=', $from)
                ->whereNotIn('status', $nonRevenueStatuses)
                ->groupBy('day')
                ->orderBy('day')
                ->get()
            : collect();

        $salesChart = [
            'labels' => $salesSeries->pluck('day')->toArray(),
            // V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding
            'data' => $salesSeries->pluck('total')->map(fn ($v) => decimal_float($v))->toArray(),
        ];

        // SECURITY: The selectRaw uses only hardcoded column/function names (status, COUNT)
        $contractSeries = class_exists($contractModel)
            ? $contractModel::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get()
            : collect();

        $contractsChart = [
            'labels' => $contractSeries->pluck('status')->toArray(),
            'data' => $contractSeries->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
        ];
    @endphp

    <div class="grid gap-4 md:grid-cols-4">
        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ __('Sales') }} ({{ __('total') }})
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ number_format($salesStats['total'] ?? 0, 2) }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Today') }}:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ number_format($salesStats['today'] ?? 0, 2) }}
                </span>
            </p>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                HRM
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ $hrStats['employees'] ?? 0 }} employees
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Attendance today:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $hrStats['today_attendance'] ?? 0 }}
                </span>
            </p>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Rentals
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ $rentalStats['units'] ?? 0 }} units
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Active contracts:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $rentalStats['active_contracts'] ?? 0 }}
                </span>
            </p>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Meta
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ $metaStats['branches'] ?? 0 }} branches
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Customers:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $metaStats['customers'] ?? 0 }}
                </span>
            </p>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="erp-card p-4 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    Sales (last 14 days)
                </h2>
            </div>
            <div wire:ignore>
                <canvas id="systemSalesChart" class="w-full h-48"></canvas>
            </div>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    {{ __('Contracts by status') }}
                </h2>
            </div>
            <div wire:ignore>
                <canvas id="systemContractsChart" class="w-full h-48"></canvas>
            </div>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="erp-card p-4 rounded-2xl">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50 mb-3">
                {{ __('Latest sales') }}
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200/80 dark:border-slate-700/80 text-left text-xs uppercase text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="py-2 pr-4">{{ __('Code') }}</th>
                            <th class="py-2 pr-4">{{ __('Customer') }}</th>
                            <th class="py-2 pr-4">{{ __('Grand total') }}</th>
                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200">
                        @php
                            $saleModel = '\\App\\Models\\Sale';
                            $latestSales = class_exists($saleModel)
                                ? $saleModel::with('customer')->orderByDesc('created_at')->limit(8)->get()
                                : collect();
                        @endphp
                        @forelse($latestSales as $sale)
                            <tr class="erp-pos-row">
                                <td class="py-2 pr-4">
                                    {{ $sale->code ?? ('#'.$sale->id) }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ optional($sale->customer)->name ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ number_format($sale->grand_total ?? 0, 2) }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $sale->created_at }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No sales found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                            <th class="py-2 pr-4">{{ __('Unit') }}</th>
                            <th class="py-2 pr-4">{{ __('Tenant') }}</th>
                            <th class="py-2 pr-4">{{ __('End date') }}</th>
                            <th class="py-2 pr-4">{{ __('Rent') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200">
                        @php
                            $contractModel = '\\App\\Models\\RentalContract';
                            $soon = class_exists($contractModel)
                                ? $contractModel::with(['unit','tenant'])
                                    ->where('status','active')
                                    ->whereDate('end_date','<=', now()->addDays(30)->toDateString())
                                    ->orderBy('end_date')
                                    ->limit(8)
                                    ->get()
                                : collect();
                        @endphp
                        @forelse($soon as $contract)
                            <tr class="erp-pos-row">
                                <td class="py-2 pr-4">
                                    {{ optional($contract->unit)->code ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ optional($contract->tenant)->name ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $contract->end_date }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ number_format($contract->rent ?? 0, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No expiring contracts found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@script
    // NEW-02 FIX: Use @script block to prevent duplicate handler registration
    // Charts are now scoped to this component and properly cleaned up on navigation
    const componentId = 'admin-dashboard-' + ($wire.__instance?.id ?? Math.random().toString(36).substr(2, 9));
    
    // Initialize global chart storage if not exists
    window.__lwCharts = window.__lwCharts || {};
    
    // Destroy any existing charts for this component
    if (window.__lwCharts[componentId + ':sales']) {
        window.__lwCharts[componentId + ':sales'].destroy();
        delete window.__lwCharts[componentId + ':sales'];
    }
    if (window.__lwCharts[componentId + ':contracts']) {
        window.__lwCharts[componentId + ':contracts'].destroy();
        delete window.__lwCharts[componentId + ':contracts'];
    }
    
    // Initialize charts
    function initAdminDashboardCharts() {
        const salesCtx = document.getElementById('systemSalesChart')?.getContext('2d');
        const contractsCtx = document.getElementById('systemContractsChart')?.getContext('2d');
        
        const salesData = @json($salesChart);
        const contractsData = @json($contractsChart);
        
        if (salesCtx && salesData.labels && salesData.labels.length) {
            window.__lwCharts[componentId + ':sales'] = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: salesData.labels,
                    datasets: [{
                        label: 'Sales',
                        data: salesData.data,
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
        
        if (contractsCtx && contractsData.labels && contractsData.labels.length) {
            window.__lwCharts[componentId + ':contracts'] = new Chart(contractsCtx, {
                type: 'doughnut',
                data: {
                    labels: contractsData.labels,
                    datasets: [{
                        data: contractsData.data,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    }
    
    // Load Chart.js if not already loaded, then initialize
    if (typeof Chart === 'undefined') {
        const scriptEl = document.createElement('script');
        scriptEl.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        scriptEl.onload = initAdminDashboardCharts;
        document.head.appendChild(scriptEl);
    } else {
        initAdminDashboardCharts();
    }
    
    // Clean up when navigating away
    document.addEventListener('livewire:navigating', () => {
        if (window.__lwCharts[componentId + ':sales']) {
            window.__lwCharts[componentId + ':sales'].destroy();
            delete window.__lwCharts[componentId + ':sales'];
        }
        if (window.__lwCharts[componentId + ':contracts']) {
            window.__lwCharts[componentId + ':contracts'].destroy();
            delete window.__lwCharts[componentId + ':contracts'];
        }
    }, { once: true });
@endscript
