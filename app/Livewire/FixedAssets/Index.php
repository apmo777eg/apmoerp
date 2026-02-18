<?php

declare(strict_types=1);

namespace App\Livewire\FixedAssets;

use App\Models\FixedAsset;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use HasSortableColumns;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $category = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * Define allowed sort columns to prevent SQL injection.
     */
    protected function allowedSortColumns(): array
    {
        return ['id', 'name', 'asset_code', 'serial_number', 'status', 'category', 'purchase_cost', 'book_value', 'created_at', 'updated_at'];
    }

    public function mount(): void
    {
        $this->authorize('fixed-assets.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getStatistics(): array
    {
        // Optimize with single query using conditional aggregations
        // NOTE: FixedAsset is branch-scoped. We intentionally avoid manual branch filters
        // so it respects the current branch context.
        $stats = FixedAsset::query()
            ->selectRaw('
                COUNT(*) as total_assets,
                COUNT(CASE WHEN status = ? THEN 1 END) as active_assets,
                SUM(CASE WHEN status = ? THEN purchase_cost ELSE 0 END) as total_value,
                SUM(CASE WHEN status = ? THEN book_value ELSE 0 END) as total_book_value
            ', ['active', 'active', 'active'])
            ->first();

        return [
            'total_assets' => $stats->total_assets ?? 0,
            'active_assets' => $stats->active_assets ?? 0,
            'total_value' => $stats->total_value ?? 0,
            'total_book_value' => $stats->total_book_value ?? 0,
        ];
    }

    public function render()
    {
        $query = FixedAsset::query()
            ->with(['branch', 'supplier', 'assignedTo']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('asset_code', 'like', "%{$this->search}%")
                    ->orWhere('serial_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->category) {
            $query->where('category', $this->category);
        }

        $query->orderBy($this->getSortField(), $this->getSortDirection());

        $assets = $query->paginate(15);
        $statistics = $this->getStatistics();

        $categories = FixedAsset::query()
            ->select('category')
            ->distinct()
            ->pluck('category');

        return view('livewire.fixed-assets.index', [
            'assets' => $assets,
            'statistics' => $statistics,
            'categories' => $categories,
        ]);
    }
}
