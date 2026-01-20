<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $search = '';

    public ?string $status = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.attendance.view')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
    }

    public function updatingToDate(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.attendance.view')) {
            abort(403);
        }

        $query = Attendance::query()
            ->with('employee')
            ->when($this->branchId, function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->when($this->search !== null && $this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->whereHas('employee', function ($employeeQuery) use ($term) {
                    $employeeQuery->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            // V46-CRIT-01 FIX: Use canonical column name 'attendance_date' instead of 'date'
            ->when($this->fromDate, function ($q) {
                $q->whereDate('attendance_date', '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($q) {
                $q->whereDate('attendance_date', '<=', $this->toDate);
            })
            ->orderByDesc('attendance_date')
            ->orderByDesc('id');

        $attendance = $query->paginate(20);

        return view('livewire.hrm.attendance.index', [
            'records' => $attendance,
        ]);
    }
}
