<div class="space-y-6">
    @include('components.erp.breadcrumb', [
        'items' => [
            ['label' => __('HRM')],
            ['label' => __('Reports')],
        ],
    ])

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-50">
                {{ __('HRM Reports') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Attendance overview and payroll summary.') }}
            </p>
        </div>
        <div class="flex items-center space-x-3">
            {{-- LOW-002 FIX: Export functionality not yet implemented - buttons removed --}}
            {{-- Export routes need to be defined in routes/web.php before enabling:
                 - Route::get('hrm/reports/attendance/export', [AttendanceReportController::class, 'export'])->name('app.hrm.reports.attendance.export');
                 - Route::get('hrm/reports/payroll/export', [PayrollReportController::class, 'export'])->name('app.hrm.reports.payroll.export');
            --}}
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ __('Attendance (last :days days)', ['days' => $filters['attendance_days']]) }}
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ $attendanceSummary['total'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Today') }}:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $attendanceSummary['today'] ?? 0 }}
                </span>
            </p>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ __('Payroll records') }}
            </h3>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                {{ $payrollSummary['total_records'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Total net') }}:
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ number_format($payrollSummary['total_net'] ?? 0, 2) }}
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
                        {{ __('Attendance window (days)') }}
                    </label>
                    <select wire:model="filters.attendance_days" class="erp-input">
                        <option value="7">{{ __('Last 7 days') }}</option>
                        <option value="14">{{ __('Last 14 days') }}</option>
                        <option value="30">{{ __('Last 30 days') }}</option>
                        <option value="90">{{ __('Last 90 days') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('Payroll period') }}
                    </label>
                    <input type="text" wire:model.live.debounce.500ms="filters.payroll_period"
                           class="erp-input"
                           placeholder="{{ __('e.g. 2025-01') }}" />
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="erp-card p-4 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    {{ __('Attendance trend') }}
                </h2>
            </div>
            <div wire:ignore>
                <canvas id="hrmAttendanceChart" class="w-full h-48"></canvas>
            </div>
        </div>

        <div class="erp-card p-4 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    {{ __('Payroll by period') }}
                </h2>
            </div>
            <div wire:ignore>
                <canvas id="hrmPayrollChart" class="w-full h-48"></canvas>
            </div>
        </div>
    </div>

    <div class="erp-card p-4 rounded-2xl">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50 mb-3">
            {{ __('Latest attendance') }}
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200/80 dark:border-slate-700/80 text-left text-xs uppercase text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="py-2 pr-4">{{ __('Employee') }}</th>
                        <th class="py-2 pr-4">{{ __('Date') }}</th>
                        <th class="py-2 pr-4">{{ __('Status') }}</th>
                        <th class="py-2 pr-4">{{ __('Approved at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200">
                    @php
                        $attendanceModel = '\\App\\Models\\Attendance';
                        $latest = class_exists($attendanceModel)
                            ? $attendanceModel::with('employee')->orderByDesc('date')->limit(10)->get()
                            : collect();
                    @endphp
                    @forelse($latest as $row)
                        <tr class="erp-pos-row">
                            <td class="py-2 pr-4">
                                {{ optional($row->employee)->name ?? '—' }}
                            </td>
                            <td class="py-2 pr-4">
                                {{ $row->date }}
                            </td>
                            <td class="py-2 pr-4">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs
                                    @if($row->status === 'approved')
                                        bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                                    @elseif($row->status === 'pending')
                                        bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                                    @else
                                        bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300
                                    @endif">
                                    {{ ucfirst($row->status) }}
                                </span>
                            </td>
                            <td class="py-2 pr-4">
                                {{ $row->approved_at }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ __('No attendance records found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Livewire 4 FIX: Use DOMContentLoaded for initial load and 'livewire:navigated' for SPA navigation support
        function initHRMCharts() {
            const attendanceCtx = document.getElementById('hrmAttendanceChart')?.getContext('2d');
            const payrollCtx = document.getElementById('hrmPayrollChart')?.getContext('2d');

            const attendanceData = @json($attendanceChart);
            const payrollData = @json($payrollChart);

            if (attendanceCtx && attendanceData.labels && attendanceData.labels.length) {
                new Chart(attendanceCtx, {
                    type: 'line',
                    data: {
                        labels: attendanceData.labels,
                        datasets: [{
                            label: 'Attendance',
                            data: attendanceData.data,
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            if (payrollCtx && payrollData.labels && payrollData.labels.length) {
                new Chart(payrollCtx, {
                    type: 'bar',
                    data: {
                        labels: payrollData.labels,
                        datasets: [{
                            label: 'Net salary',
                            data: payrollData.data,
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

        // Initialize on DOM ready (initial page load)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHRMCharts);
        } else {
            initHRMCharts();
        }

        // Re-initialize after Livewire SPA navigation
        document.addEventListener('livewire:navigated', initHRMCharts);
    </script>
@endpush
