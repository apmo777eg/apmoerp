<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Traits\HasExport;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasExport;
    use HasSortableColumns;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $customerType = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public string $paginationMode = 'load-more';

    public int $perPage = 15;

    public int $loadMorePage = 1;

    public bool $hasMorePages = true;

    public ?int $branchId = null;

    public bool $isSuperAdmin = false;

    public bool $canManageAll = false;

    /**
     * Define allowed sort columns to prevent SQL injection.
     */
    protected function allowedSortColumns(): array
    {
        // FIX: customers table uses `type` (individual/company)
        return ['id', 'name', 'email', 'phone', 'type', 'created_at', 'updated_at'];
    }

    public function mount(): void
    {
        $this->authorize('customers.view');
        $user = auth()->user();
        $this->branchId = $this->resolveBranchId($user);
        $this->isSuperAdmin = $this->userIsSuperAdmin($user);
        $this->canManageAll = $this->isSuperAdmin || (bool) $user?->can('customers.manage.all');

        if (! $this->branchId && ! $this->canManageAll) {
            abort(403, __('You must be assigned to a branch to view customers.'));
        }

        $this->initializeExport('customers');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->loadMorePage = 1;
    }

    public function loadMore(): void
    {
        $this->loadMorePage++;
    }

    /**
     * Override sortBy to also reset load more pagination.
     */
    public function sortBy(string $field): void
    {
        // Validate field is in allowed list
        if (! in_array($field, $this->allowedSortColumns(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadMorePage = 1;
    }

    public function delete(int $id): void
    {
        $this->authorize('customers.manage');
        $this->ensureBranchAccess();
        $query = Customer::query();

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $query->findOrFail($id)->delete();
        session()->flash('success', __('Customer deleted successfully'));
    }

    public function render()
    {
        $query = Customer::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->search, function ($q) {
                $q->where(function ($searchQuery) {
                    $searchQuery->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            // FIX: filter by customers.type (individual/company)
            ->when($this->customerType, fn ($q) => $q->where('type', $this->customerType))
            ->orderBy($this->getSortField(), $this->getSortDirection());

        if ($this->paginationMode === 'load-more') {
            $total = (clone $query)->count();
            $customers = $query->take($this->loadMorePage * $this->perPage)->get();
            $this->hasMorePages = $customers->count() < $total;
        } else {
            $customers = $query->paginate($this->perPage);
            $this->hasMorePages = $customers->hasMorePages();
        }

        return view('livewire.customers.index', [
            'customers' => $customers,
            'paginationMode' => $this->paginationMode,
            'hasMorePages' => $this->hasMorePages,
        ])->layout('layouts.app', ['title' => __('Customers')]);
    }

    public function export()
    {
        $this->authorize('customers.view');
        $this->ensureBranchAccess();
        $data = Customer::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->search, function ($q) {
                $q->where(function ($searchQuery) {
                    $searchQuery->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            // FIX: filter by customers.type (individual/company)
            ->when($this->customerType, fn ($q) => $q->where('type', $this->customerType))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'address',
                'balance',
                'type',
                'created_at',
            ])
            ->get();

        return $this->performExport('customers', $data, __('Customers Export'));
    }

    private function resolveBranchId(?User $user): ?int
    {
        return $user?->branch_id ?? $user?->branches()->first()?->id;
    }

    private function userIsSuperAdmin(?User $user): bool
    {
        return (bool) $user?->hasAnyRole(['super-admin', 'Super Admin']);
    }

    private function ensureBranchAccess(): void
    {
        if (! $this->branchId && ! $this->canManageAll) {
            abort(403, __('You must be assigned to a branch to view customers.'));
        }
    }
}
