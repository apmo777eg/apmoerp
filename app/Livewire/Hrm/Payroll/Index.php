<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Payroll;

use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
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
            ->when($this->period, function ($q) {
                try {
                    $dt = Carbon::createFromFormat('Y-m', (string) $this->period);
                    $q->where('year', (int) $dt->year)->where('month', (int) $dt->month);
                } catch (\Throwable) {
                    // Ignore invalid input; UI validates period format.
                }
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id');

        $runs = $query->paginate(20);

        return view('livewire.hrm.payroll.index', [
            'runs' => $runs,
        ]);
    }
}
