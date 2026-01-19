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

        $builder = $model::query();
        $builder->whereDate('date', '>=', $fromDate);

        $summary = [
            'total' => (clone $builder)->count(),
            'today' => (clone $builder)->whereDate('date', now()->toDateString())->count(),
        ];

        $attendanceRecords = (clone $builder)->get(['status', 'date']);

        $statusCounts = $attendanceRecords->groupBy('status')
            ->map->count()
            ->toArray();

        $summary['by_status'] = $statusCounts;

        $series = $attendanceRecords->groupBy('date')
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
