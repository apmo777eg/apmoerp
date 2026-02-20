<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\SelfService;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

/**
 * My Attendance - Employee Self Service
 * Allows employees to view their own attendance records
 */
class MyAttendance extends Component
{
    use WithPagination;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public ?string $status = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('employee.self.attendance')) {
            abort(403);
        }

        // Default to current month
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
    }

    public function updatingToDate(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Get attendance statistics for the current period
     */
    public function getStatistics(): array
    {
        $user = Auth::user();

        if (! $user || ! $user->employee_id) {
            return [
                'total_days' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'early_leave' => 0,
            ];
        }

        $query = Attendance::where('employee_id', $user->employee_id)
            ->when($this->fromDate, fn ($q) => $q->whereDate('attendance_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('attendance_date', '<=', $this->toDate));

        return [
            'total_days' => (clone $query)->count(),
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'late' => (clone $query)->where('late_minutes', '>', 0)->count(),
            'early_leave' => (clone $query)->where('early_leave_minutes', '>', 0)->count(),
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('employee.self.attendance')) {
            abort(403);
        }

        // Get employee_id from user (assuming there's a relationship)
        $employeeId = $user->employee_id ?? null;

        $records = collect();

        if ($employeeId) {
            $records = Attendance::where('employee_id', $employeeId)
                ->when($this->fromDate, fn ($q) => $q->whereDate('attendance_date', '>=', $this->fromDate))
                ->when($this->toDate, fn ($q) => $q->whereDate('attendance_date', '<=', $this->toDate))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->orderByDesc('attendance_date')
                ->paginate(20);
        }

        return view('livewire.hrm.self-service.my-attendance', [
            'records' => $records,
            'statistics' => $this->getStatistics(),
        ]);
    }
}
