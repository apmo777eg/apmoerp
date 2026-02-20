<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Serials;

use App\Models\InventorySerial;
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
     * Hard allow-list of sortable fields to avoid SQL injection.
     */
    protected array $allowedSortFields = [
        'created_at',
        'serial_number',
        'unit_cost',
        'warranty_end',
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
        // InventorySerial is branch-scoped. Do not manually force the user's branch.
        $stats = InventorySerial::query()
            ->selectRaw('
                COUNT(*) as total_serials,
                COUNT(CASE WHEN status = ? THEN 1 END) as in_stock,
                COUNT(CASE WHEN status = ? THEN 1 END) as sold,
                COUNT(CASE WHEN warranty_end IS NOT NULL AND warranty_end >= ? THEN 1 END) as under_warranty
            ', ['in_stock', 'sold', now()])
            ->first();

        return [
            'total_serials' => $stats->total_serials ?? 0,
            'in_stock' => $stats->in_stock ?? 0,
            'sold' => $stats->sold ?? 0,
            'under_warranty' => $stats->under_warranty ?? 0,
        ];
    }

    public function render()
    {
        $query = InventorySerial::query()
            ->with(['product', 'warehouse', 'customer']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('serial_number', 'like', "%{$this->search}%")
                    ->orWhereHas('product', function ($pq) {
                        $pq->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if (! in_array($this->sortField, $this->allowedSortFields, true)) {
            $this->sortField = 'created_at';
        }
        $query->orderBy($this->sortField, $this->sortDirection);

        $serials = $query->paginate(15);
        $statistics = $this->getStatistics();

        return view('livewire.inventory.serials.index', [
            'serials' => $serials,
            'statistics' => $statistics,
        ]);
    }
}
