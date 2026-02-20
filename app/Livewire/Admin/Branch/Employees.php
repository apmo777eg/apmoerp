<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branch;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

/**
 * Branch Employees - Branch Admin Page
 * Allows branch admins to view and manage employees within their branch
 */
class Employees extends Component
{
    use WithPagination;

    public ?Branch $branch = null;

    public ?string $search = '';

    public ?string $status = null;

    public ?string $role = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.employees.manage')) {
            abort(403);
        }

        $this->branch = $user->branch;

        if (! $this->branch) {
            abort(403, __('No branch assigned to this user.'));
        }

        // Check if user is a branch admin
        if (! $user->isBranchAdmin($this->branch->id) && ! $user->hasRole('Super Admin')) {
            abort(403);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle employee active status
     */
    public function toggleStatus(int $userId): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.employees.manage')) {
            abort(403);
        }

        $employee = User::where('branch_id', $this->branch->id)
            ->where('id', $userId)
            ->first();

        if ($employee && $employee->id !== $user->id) {
            $employee->update(['is_active' => ! $employee->is_active]);
            session()->flash('success', __('Employee status updated.'));
        }
    }

    /**
     * Get role options for the branch
     */
    public function getRoleOptions(): array
    {
        return [
            'Branch Admin' => __('Branch Admin'),
            'Branch Manager' => __('Branch Manager'),
            'Branch Supervisor' => __('Branch Supervisor'),
            'Branch Cashier' => __('Branch Cashier'),
            'Branch Employee' => __('Branch Employee'),
        ];
    }

    /**
     * Get employee statistics
     */
    public function getStatistics(): array
    {
        $query = User::where('branch_id', $this->branch->id);

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'inactive' => (clone $query)->where('is_active', false)->count(),
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.employees.manage')) {
            abort(403);
        }

        $employees = User::where('branch_id', $this->branch->id)
            ->with('roles')
            ->when($this->search !== null && $this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($this->status !== null, function ($q) {
                $q->where('is_active', $this->status === 'active');
            })
            ->when($this->role !== null, function ($q) {
                $q->whereHas('roles', function ($query) {
                    $query->where('name', $this->role);
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.branch.employees', [
            'employees' => $employees,
            'roleOptions' => $this->getRoleOptions(),
            'statistics' => $this->getStatistics(),
        ]);
    }
}
