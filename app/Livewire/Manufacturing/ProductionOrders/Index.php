<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing\ProductionOrders;

use App\Livewire\Manufacturing\Concerns\StatsCacheVersion;
use App\Models\ProductionOrder;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasSortableColumns;
    use StatsCacheVersion;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('manufacturing.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function allowedSortColumns(): array
    {
        return [
            'created_at',
            'reference_number',
            'status',
            'priority',
            'planned_quantity',
            'produced_quantity',
        ];
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $baseQuery = ProductionOrder::query()
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->priority, fn ($q) => $q->where('priority', $this->priority));

        if ($user && $user->branch_id) {
            $baseQuery->where('branch_id', $user->branch_id);
        }

        $cacheKey = sprintf(
            'production_orders_stats_%s_%s_%s_%s',
            $user?->branch_id ?? 'all',
            $this->status ?: 'all',
            $this->priority ?: 'all',
            $this->statsCacheVersion($baseQuery)
        );

        return Cache::remember($cacheKey, 300, function () use ($baseQuery) {
            return [
                'total_orders' => (clone $baseQuery)->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'planned_quantity' => (clone $baseQuery)->sum('planned_quantity'),
                'produced_quantity' => (clone $baseQuery)->sum('produced_quantity'),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $orders = ProductionOrder::query()
            ->with(['product', 'bom', 'warehouse', 'branch'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('reference_number', 'like', "%{$this->search}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('bom', fn ($b) => $b->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->priority, fn ($q) => $q->where('priority', $this->priority))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.manufacturing.production-orders.index', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }
}
