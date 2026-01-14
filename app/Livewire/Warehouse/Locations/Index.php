<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Locations;

use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
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

    public function delete(int $id): void
    {
        $this->authorize('warehouse.manage');

        $warehouse = Warehouse::findOrFail($id);

        $user = auth()->user();
        if ($user->branch_id && $warehouse->branch_id !== $user->branch_id) {
            abort(403);
        }

        $warehouse->delete();
        Cache::forget('warehouses_stats_'.($user->branch_id ?? 'all'));
        session()->flash('success', __('Warehouse deleted successfully'));
    }

    public function render()
    {
        $user = auth()->user();

        // STILL-V11-CRITICAL-01 FIX: Map status filter to is_active column
        // Warehouse.status is a computed accessor (getStatusAttribute) that returns
        // 'active'|'inactive' based on the is_active boolean column
        $warehouses = Warehouse::query()
            ->withCount('stockMovements')
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->statusFilter, function ($q) {
                // Translate UI filter values to actual column
                $isActive = $this->statusFilter === 'active';

                return $q->where('is_active', $isActive);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $stats = [
            'total' => Warehouse::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))->count(),
            'active' => Warehouse::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', true)->count(),
            'inactive' => Warehouse::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', false)->count(),
        ];

        return view('livewire.warehouse.locations.index', [
            'warehouses' => $warehouses,
            'stats' => $stats,
        ]);
    }
}
