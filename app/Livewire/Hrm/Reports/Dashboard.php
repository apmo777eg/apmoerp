<?php

namespace App\Livewire\Hrm\Reports;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public array $filters = [
        'attendance_days' => 14,
        'payroll_period' => null,
    ];

    public array $attendanceChart = [];

    public array $payrollChart = [];

    public array $attendanceSummary = [];

    public array $payrollSummary = [];

    public function mount(): void
    {

        $user = Auth::user();
        if (! $user || ! $user->can('hr.view-reports')) {
            abort(403);
        }

        if (auth()->check()) {
            abort_unless(auth()->user()->can('hr.view-reports'), 403);
        }

        $this->loadData();
    }

    public function updatedFilters(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->loadAttendanceData();
        $this->loadPayrollData();
    }

    /**
     * Get the authenticated user's branch ID for scoping queries.
     * V48-CRIT-02 FIX: Centralize branch scoping logic to prevent code duplication.
     */
    protected function getUserBranchId(): ?int
    {
        $user = auth()->user();

        return $user?->branch_id;
    }

    /**
     * Apply branch scoping to a query builder.
     * V48-CRIT-02 FIX: Prevent cross-branch data leakage in multi-branch ERP.
     */
    protected function applyBranchScope($builder): void
    {
        $branchId = $this->getUserBranchId();
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
    }

    protected function loadAttendanceData(): void
    {
        $model = '\\App\\Models\\Attendance';

        if (! class_exists($model)) {
            $this->attendanceChart = [];
            $this->attendanceSummary = [];

            return;
        }

        $days = (int) ($this->filters['attendance_days'] ?? 14);
        $fromDate = now()->subDays($days)->toDateString();

        // V48-CRIT-02 FIX: Use canonical column 'attendance_date' instead of 'date'
        // and add branch scoping for multi-branch security
        $builder = $model::query();
        $builder->whereDate('attendance_date', '>=', $fromDate);

        // V48-CRIT-02 FIX: Add branch scoping to prevent cross-branch data leakage
        $this->applyBranchScope($builder);

        $summary = [
            'total' => (clone $builder)->count(),
            // V48-CRIT-02 FIX: Use canonical column 'attendance_date'
            'today' => (clone $builder)->whereDate('attendance_date', now()->toDateString())->count(),
        ];

        // V48-CRIT-02 FIX: Use canonical column 'attendance_date'
        $attendanceRecords = (clone $builder)->get(['status', 'attendance_date']);

        $statusCounts = $attendanceRecords->groupBy('status')
            ->map->count()
            ->toArray();

        $summary['by_status'] = $statusCounts;

        // V48-CRIT-02 FIX: Use canonical column 'attendance_date'
        $series = $attendanceRecords->groupBy('attendance_date')
            ->sortKeys()
            ->map(function ($group, $day) {
                return [
                    'day' => $day,
                    'total' => $group->count(),
                ];
            })
            ->values()
            ->all();

        $this->attendanceSummary = $summary;
        $this->attendanceChart = [
            'labels' => array_map(fn ($row) => $row['day'], $series),
            'data' => array_map(fn ($row) => $row['total'], $series),
        ];
    }

    protected function loadPayrollData(): void
    {
        $model = '\\App\\Models\\Payroll';

        if (! class_exists($model)) {
            $this->payrollChart = [];
            $this->payrollSummary = [];

            return;
        }

        $builder = $model::query();

        // V48-CRIT-02 FIX: Add branch scoping to prevent cross-branch data leakage
        $this->applyBranchScope($builder);

        if (! empty($this->filters['payroll_period'])) {
            $builder->where('period', $this->filters['payroll_period']);
        }

        $summary = [
            'total_records' => (clone $builder)->count(),
            'total_net' => (clone $builder)->sum('net'),
        ];

        $payrollRecords = (clone $builder)->get(['period', 'net']);

        $series = $payrollRecords->groupBy('period')
            ->sortKeys()
            ->map(function ($group, $period) {
                return [
                    'period' => $period,
                    'total_net' => decimal_float($group->sum('net')),
                ];
            })
            ->values()
            ->all();

        $this->payrollSummary = $summary;
        $this->payrollChart = [
            'labels' => array_map(fn ($row) => $row['period'], $series),
            'data' => array_map(fn ($row) => $row['total_net'], $series),
        ];
    }

    public function render()
    {
        return view('livewire.hrm.reports.dashboard');
    }
}
