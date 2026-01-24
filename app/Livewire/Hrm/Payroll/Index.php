<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Payroll;

use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $search = '';

    public ?string $status = null;

    public ?string $period = null; // e.g. "2024-01"

    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.payroll.view')) {
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

    public function updatingPeriod(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.payroll.view')) {
            abort(403);
        }

        $query = Payroll::query()
            ->with('employee')
            ->when($this->branchId, function ($q) {
                $q->whereHas('employee', function ($employeeQuery) {
                    $employeeQuery->where('branch_id', $this->branchId);
                });
            })
            ->when($this->search !== null && $this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->whereHas('employee', function ($employeeQuery) use ($term) {
                    $employeeQuery->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('employee_code', 'like', $term);
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->period, function ($q) {
                $q->where('period', $this->period);
            })
            ->orderByDesc('period')
            ->orderByDesc('id');

        $runs = $query->paginate(20);

        return view('livewire.hrm.payroll.index', [
            'runs' => $runs,
        ]);
    }
}
