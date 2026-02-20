<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing\WorkCenters;

use App\Livewire\Manufacturing\Concerns\StatsCacheVersion;
use App\Models\WorkCenter;
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

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

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
            'name',
            'code',
            'status',
            'capacity_per_hour',
            'cost_per_hour',
            'created_at',
        ];
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $baseQuery = WorkCenter::query()
            ->when($this->status, fn ($q) => $q->where('status', $this->status));

        if ($user && $user->branch_id) {
            $baseQuery->where('branch_id', $user->branch_id);
        }

        $cacheKey = sprintf(
            'work_centers_stats_%s_%s_%s',
            $user?->branch_id ?? 'all',
            $this->status ?: 'all',
            $this->statsCacheVersion($baseQuery)
        );

        return Cache::remember($cacheKey, 300, function () use ($baseQuery) {
            return [
                'total_centers' => (clone $baseQuery)->count(),
                'active_centers' => (clone $baseQuery)->where('status', 'active')->count(),
                'total_capacity' => (clone $baseQuery)->sum('capacity_per_hour'),
                'avg_cost_per_hour' => (clone $baseQuery)->avg('cost_per_hour') ?? 0,
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $workCenters = WorkCenter::query()
            ->with(['branch'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('name_ar', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.manufacturing.work-centers.index', [
            'workCenters' => $workCenters,
            'stats' => $stats,
        ]);
    }
}
