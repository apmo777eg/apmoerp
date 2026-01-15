<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Transfers;

use App\Models\Transfer;
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
    public string $statusFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('warehouse.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function approve(int $id): void
    {
        $this->authorize('warehouse.manage');

        $transfer = Transfer::findOrFail($id);

        $user = auth()->user();
        if ($user->branch_id && $transfer->branch_id !== $user->branch_id) {
            abort(403);
        }

        $transfer->update(['status' => 'completed']);

        session()->flash('success', __('Transfer approved successfully'));
    }

    public function cancel(int $id): void
    {
        $this->authorize('warehouse.manage');

        $transfer = Transfer::findOrFail($id);

        $user = auth()->user();
        if ($user->branch_id && $transfer->branch_id !== $user->branch_id) {
            abort(403);
        }

        $transfer->update(['status' => 'cancelled']);

        session()->flash('success', __('Transfer cancelled'));
    }

    public function delete(int $id): void
    {
        $this->authorize('warehouse.manage');

        $transfer = Transfer::findOrFail($id);

        $user = auth()->user();
        if ($user->branch_id && $transfer->branch_id !== $user->branch_id) {
            abort(403);
        }

        $transfer->delete();

        session()->flash('success', __('Transfer deleted successfully'));
    }

    public function render()
    {
        $user = auth()->user();

        $query = Transfer::with(['fromWarehouse', 'toWarehouse', 'items.product'])
            ->when($user->branch_id, fn ($q) => $q->where('transfers.branch_id', $user->branch_id))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    // V24-CRIT-02 FIX: Use 'notes' column (per migration) instead of 'note'
                    $query->where('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('fromWarehouse', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('toWarehouse', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection);

        $transfers = $query->paginate(15);

        // Statistics
        $stats = [
            'total' => Transfer::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))->count(),
            'pending' => Transfer::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'pending')->count(),
            'completed' => Transfer::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'completed')->count(),
            'cancelled' => Transfer::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'cancelled')->count(),
        ];

        return view('livewire.warehouse.transfers.index', [
            'transfers' => $transfers,
            'stats' => $stats,
        ]);
    }
}
