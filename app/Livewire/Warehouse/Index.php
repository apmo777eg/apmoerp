<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse;

use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\BranchContextManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $activeTab = 'warehouses';

    public function mount(): void
    {
        $this->authorize('warehouse.view');
    }

    #[Url]
    public string $search = '';

    #[Url]
    public string $warehouseId = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('warehouse.manage');

        $warehouse = Warehouse::find($id);
        if ($warehouse) {
            if ($warehouse->stockMovements()->count() > 0) {
                session()->flash('error', __('Cannot delete warehouse with stock movements'));

                return;
            }
            $warehouse->delete();
            session()->flash('success', __('Warehouse deleted successfully'));

            $user = auth()->user();
            $branchId = current_branch_id() ?? ($user?->branch_id ? (int) $user->branch_id : null);
            if ($user && BranchContextManager::canViewAllBranches($user) && current_branch_id() === null) {
                $branchId = null;
            }
            Cache::forget('warehouse_stats_'.branch_context_cache_key($branchId));
            Cache::forget('all_warehouses_'.branch_context_cache_key($branchId));
        }
    }

    public function toggleStatus(int $id): void
    {
        $this->authorize('warehouse.manage');

        $warehouse = Warehouse::find($id);
        if ($warehouse) {
            $newStatus = $warehouse->status === 'active' ? 'inactive' : 'active';
            $warehouse->update(['status' => $newStatus]);

            $user = auth()->user();
            $branchId = current_branch_id() ?? ($user?->branch_id ? (int) $user->branch_id : null);
            if ($user && BranchContextManager::canViewAllBranches($user) && current_branch_id() === null) {
                $branchId = null;
            }
            Cache::forget('warehouse_stats_'.branch_context_cache_key($branchId));
            Cache::forget('all_warehouses_'.branch_context_cache_key($branchId));
        }
    }

    public function getStatistics(): array
    {
        $user = auth()->user();

        $branchId = current_branch_id() ?? ($user?->branch_id ? (int) $user->branch_id : null);
        if ($user && BranchContextManager::canViewAllBranches($user) && current_branch_id() === null) {
            $branchId = null;
        }

        $cacheKey = 'warehouse_stats_'.branch_context_cache_key($branchId);

        return Cache::remember($cacheKey, 300, function () use ($user, $branchId) {
            $warehouseQuery = Warehouse::query();

            if ($branchId) {
                $warehouseQuery->where('branch_id', $branchId);
            }

            $stockMovementQuery = StockMovement::query();
            if ($branchId) {
                $stockMovementQuery->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId));
            }
            // quantity is signed: positive = in, negative = out
            // SECURITY: The selectRaw uses hardcoded column names only
            $totalStock = (clone $stockMovementQuery)->sum('quantity');
            $totalValue = (clone $stockMovementQuery)->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')->first()?->value ?? 0;

            return [
                'total_warehouses' => $warehouseQuery->count(),
                'active_warehouses' => Warehouse::query()
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->where('is_active', true)->count(),
                'total_stock' => $totalStock,
                'stock_value' => $totalValue,
                'recent_movements' => StockMovement::query()
                    ->when($branchId, fn ($q) => $q->whereHas('warehouse', fn ($wq) => $wq->where('branch_id', $branchId)))
                    ->where('created_at', '>=', now()->subDays(7))->count(),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $branchId = current_branch_id() ?? ($user?->branch_id ? (int) $user->branch_id : null);
        if ($user && BranchContextManager::canViewAllBranches($user) && current_branch_id() === null) {
            $branchId = null;
        }

        $warehouses = [];
        $movements = [];

        if ($this->activeTab === 'warehouses') {
            $warehouses = Warehouse::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15);
        } else {
            $movements = StockMovement::query()
                ->with(['product', 'warehouse'])
                ->when($branchId, fn ($q) => $q->whereHas('warehouse', fn ($wq) => $wq->where('branch_id', $branchId)))
                ->when($this->search, fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$this->search}%")))
                ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        $allWarehouses = Cache::remember('all_warehouses_'.branch_context_cache_key($branchId), 600, function () use ($branchId) {
            return Warehouse::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->get();
        });

        $stats = $this->getStatistics();

        return view('livewire.warehouse.index', [
            'warehouses' => $warehouses,
            'movements' => $movements,
            'allWarehouses' => $allWarehouses,
            'stats' => $stats,
        ]);
    }
}
