<?php

declare(strict_types=1);

namespace App\Livewire\Purchases\Requisitions;

use App\Models\PurchaseRequisition;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * Hard allow-list of sortable fields to avoid SQL injection.
     */
    protected array $allowedSortFields = [
        'created_at',
        'code',
        'subject',
        'priority',
        'status',
    ];

    public function mount(): void
    {
        $this->authorize('purchases.requisitions.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->allowedSortFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getStatistics(): array
    {
        // PurchaseRequisition is branch-scoped.
        $stats = PurchaseRequisition::query()
            ->selectRaw('
                COUNT(*) as total_requisitions,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending,
                COUNT(CASE WHEN status = ? THEN 1 END) as approved,
                COUNT(CASE WHEN status = ? THEN 1 END) as converted
            ', ['pending', 'approved', 'converted'])
            ->first();

        return [
            'total_requisitions' => $stats->total_requisitions ?? 0,
            'pending' => $stats->pending ?? 0,
            'approved' => $stats->approved ?? 0,
            'converted' => $stats->converted ?? 0,
        ];
    }

    public function approve(int $id): void
    {
        $this->authorize('purchases.requisitions.approve');

        $requisition = PurchaseRequisition::findOrFail($id);
        $requisition->approve(function_exists('actual_user_id') ? actual_user_id() : auth()->id());

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Purchase requisition approved successfully'),
        ]);
    }

    public function reject(int $id, string $reason = ''): void
    {
        $this->authorize('purchases.requisitions.approve');

        $requisition = PurchaseRequisition::findOrFail($id);
        $requisition->reject($reason ?: __('Rejected'), function_exists('actual_user_id') ? actual_user_id() : auth()->id());

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Purchase requisition rejected'),
        ]);
    }

    public function delete(int $id): void
    {
        $this->authorize('purchases.requisitions.manage');

        $requisition = PurchaseRequisition::findOrFail($id);
        $requisition->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Purchase requisition deleted successfully'),
        ]);
    }

    public function render()
    {
        $query = PurchaseRequisition::query()
            ->with(['requestedBy', 'department', 'items']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('requestedBy', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->priority) {
            $query->where('priority', $this->priority);
        }

        if (! in_array($this->sortField, $this->allowedSortFields, true)) {
            $this->sortField = 'created_at';
        }

        $requisitions = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.purchases.requisitions.index', [
            'requisitions' => $requisitions,
            'statistics' => $this->getStatistics(),
        ]);
    }
}
