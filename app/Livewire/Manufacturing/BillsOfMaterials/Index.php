<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing\BillsOfMaterials;

use App\Livewire\Manufacturing\Concerns\StatsCacheVersion;
use App\Models\BillOfMaterial;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
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
            'name',
        ];
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $baseQuery = BillOfMaterial::query()
            ->when($this->status, fn ($q) => $q->where('status', $this->status));

        if ($user && $user->branch_id) {
            $baseQuery->where('branch_id', $user->branch_id);
        }

        $cacheKey = sprintf(
            'bom_stats_%s_%s_%s',
            $user?->branch_id ?? 'all',
            $this->status ?: 'all',
            $this->statsCacheVersion($baseQuery)
        );

        return Cache::remember($cacheKey, 300, function () use ($baseQuery) {
            return [
                'total_boms' => (clone $baseQuery)->count(),
                'active_boms' => (clone $baseQuery)->where('status', 'active')->count(),
                'draft_boms' => (clone $baseQuery)->where('status', 'draft')->count(),
                'total_production_orders' => (clone $baseQuery)->withCount('productionOrders')->get()->sum('production_orders_count'),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $boms = BillOfMaterial::query()
            ->with(['product', 'branch', 'items.product'])
            ->withCount(['items', 'operations', 'productionOrders'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('reference_number', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('name_ar', 'like', "%{$this->search}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.manufacturing.bills-of-materials.index', [
            'boms' => $boms,
            'stats' => $stats,
        ]);
    }
}
