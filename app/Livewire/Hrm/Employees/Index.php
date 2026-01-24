<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Employees;

use App\Models\HREmployee;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /**
     * Simple text search across code, name, position and linked user.
     */
    #[Url(except: '')]
    public ?string $search = '';

    /**
     * Filter by active / inactive employees.
     *
     * @var null|"active"|"inactive"
     */
    #[Url(except: '')]
    public ?string $status = null;

    /**
     * Filter by department/position
     */
    #[Url(except: '')]
    public ?string $department = '';

    /**
     * Results per page
     */
    #[Url(except: 15)]
    public int $perPage = 15;

    /**
     * Current branch scope.
     */
    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.employees.view')) {
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

    public function updatingDepartment(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all filters and reset to default state
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = null;
        $this->department = '';
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.employees.view')) {
            abort(403);
        }

        $query = HREmployee::query()
            ->with(['branch', 'user'])
            ->when($this->branchId, function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->when($this->search !== null && $this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('position', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('username', 'like', $term);
                        });
                });
            })
            ->when($this->status === 'active', function ($q) {
                $q->where('is_active', true);
            })
            ->when($this->status === 'inactive', function ($q) {
                $q->where('is_active', false);
            })
            ->when($this->department !== null && $this->department !== '', function ($q) {
                $q->where('position', $this->department);
            })
            ->orderByDesc('id');

        $employees = $query->paginate($this->perPage);

        // Statistics
        $baseQuery = HREmployee::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));

        $totalEmployees = (clone $baseQuery)->count();
        $activeEmployees = (clone $baseQuery)->where('is_active', true)->count();
        $inactiveEmployees = $totalEmployees - $activeEmployees;
        $totalSalary = (clone $baseQuery)->where('is_active', true)->sum('salary');

        // Get departments/positions for filter - combine null and empty check
        $departments = HREmployee::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->whereRaw("COALESCE(position, '') != ''")
            ->distinct()
            ->pluck('position')
            ->sort()
            ->values();

        return view('livewire.hrm.employees.index', [
            'employees' => $employees,
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'inactiveEmployees' => $inactiveEmployees,
            'totalSalary' => $totalSalary,
            'departments' => $departments,
        ]);
    }
}
