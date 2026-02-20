<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Batches;

use App\Models\InventoryBatch;
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
    public string $status = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * Hard allow-list of sortable fields to avoid SQL injection in orderBy.
     */
    protected array $allowedSortFields = [
        'created_at',
        'batch_number',
        'quantity',
        'unit_cost',
        'expiry_date',
        'status',
    ];

    public function mount(): void
    {
        $this->authorize('inventory.products.view');
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
        // InventoryBatch is branch-scoped. Do not manually force the user's branch.
        $stats = InventoryBatch::query()
            ->selectRaw('
                COUNT(*) as total_batches,
                COUNT(CASE WHEN status = ? THEN 1 END) as active_batches,
                COUNT(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= ? THEN 1 END) as expired_batches,
                SUM(CASE WHEN status = ? THEN quantity ELSE 0 END) as total_quantity
            ', ['active', now()->addDays(30), 'active'])
            ->first();

        return [
            'total_batches' => $stats->total_batches ?? 0,
            'active_batches' => $stats->active_batches ?? 0,
            'expired_batches' => $stats->expired_batches ?? 0,
            'total_quantity' => $stats->total_quantity ?? 0,
        ];
    }

    public function render()
    {
        $query = InventoryBatch::query()
            ->with(['product', 'warehouse']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('batch_number', 'like', "%{$this->search}%")
                    ->orWhereHas('product', function ($pq) {
                        $pq->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Defensive: ensure sortField is always safe (e.g. when set from query params).
        if (! in_array($this->sortField, $this->allowedSortFields, true)) {
            $this->sortField = 'created_at';
        }
        $query->orderBy($this->sortField, $this->sortDirection);

        $batches = $query->paginate(15);
        $statistics = $this->getStatistics();

        return view('livewire.inventory.batches.index', [
            'batches' => $batches,
            'statistics' => $statistics,
        ]);
    }
}
