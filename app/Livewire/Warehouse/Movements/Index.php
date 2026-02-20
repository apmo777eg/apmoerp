<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Movements;

use App\Models\StockMovement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $directionFilter = '';

    #[Url]
    public ?int $warehouseFilter = null;

    #[Url]
    public ?int $productFilter = null;

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

    public function render()
    {
        $user = auth()->user();

        $query = StockMovement::with(['product', 'warehouse', 'createdBy'])
            ->when($user->branch_id, fn ($q) => $q->whereHas('warehouse', fn ($wq) => $wq->where('branch_id', $user->branch_id)))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            // Filter by direction using signed quantity (positive = in, negative = out)
            ->when($this->directionFilter, function ($q) {
                if ($this->directionFilter === 'in') {
                    $q->where('quantity', '>', 0);
                } elseif ($this->directionFilter === 'out') {
                    $q->where('quantity', '<', 0);
                }
            })
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->when($this->productFilter, fn ($q) => $q->where('product_id', $this->productFilter))
            ->orderBy($this->sortField, $this->sortDirection);

        $movements = $query->paginate(15);

        // Statistics
        $baseQuery = StockMovement::when($user->branch_id, fn ($q) => $q->whereHas('warehouse', fn ($wq) => $wq->where('branch_id', $user->branch_id)));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            // quantity > 0 = in, quantity < 0 = out
            'in' => (clone $baseQuery)->where('quantity', '>', 0)->count(),
            'out' => (clone $baseQuery)->where('quantity', '<', 0)->count(),
            // SECURITY: The selectRaw uses hardcoded column names only
            'total_value' => (clone $baseQuery)->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')->first()?->value ?? 0,
        ];

        // Get warehouses and products for filters
        $warehouses = \App\Models\Warehouse::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = \App\Models\Product::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(100) // Limit for performance
            ->get();

        return view('livewire.warehouse.movements.index', [
            'movements' => $movements,
            'stats' => $stats,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }
}
