<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Adjustments;

use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\StockMovement;
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

        $adjustment = Adjustment::findOrFail($id);

        // Check if user has access to this branch's data
        $user = auth()->user();
        if ($user->branch_id && $adjustment->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized access to this branch data');
        }

        $itemIds = $adjustment->items()->pluck('id');
        if ($itemIds->isNotEmpty()) {
            StockMovement::where('reference_type', AdjustmentItem::class)
                ->whereIn('reference_id', $itemIds)
                ->delete();

            $adjustment->items()->delete();
        }

        $adjustment->delete();

        session()->flash('success', __('Adjustment deleted successfully'));
    }

    public function render()
    {
        $user = auth()->user();

        $query = Adjustment::with(['items.product', 'items.product.category'])
            ->when($user->branch_id, fn ($q) => $q->where('adjustments.branch_id', $user->branch_id))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    // V24-CRIT-03 FIX: Remove 'note' column search - Adjustment model only has 'reason'
                    // The 'note' accessor maps to 'reason', so searching 'reason' is sufficient
                    $query->where('reason', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);

        $adjustments = $query->paginate(15);

        // Statistics
        $stats = [
            'total' => Adjustment::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))->count(),
            'this_month' => Adjustment::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'total_items' => Adjustment::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->withCount('items')
                ->get()
                ->sum('items_count'),
        ];

        return view('livewire.warehouse.adjustments.index', [
            'adjustments' => $adjustments,
            'stats' => $stats,
        ]);
    }
}
