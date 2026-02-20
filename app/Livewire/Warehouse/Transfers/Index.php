<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Transfers;

use App\Models\Transfer;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
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

    public function approve(int $id): void
    {
        $this->authorize('warehouse.manage');

        $transfer = Transfer::findOrFail($id);

        // V25-MED-02 FIX: Super admins should be able to access all branches
        $user = auth()->user();
        if (! $user->hasRole('Super Admin') && $user->branch_id && $transfer->branch_id !== $user->branch_id) {
            abort(403);
        }

        // V26-CRIT-02 FIX: Check transfer status before proceeding to ensure idempotency
        // This prevents duplicate stock movements if approve is called multiple times
        // (e.g., double-click, request replay, concurrent requests)
        if (! in_array($transfer->status, ['pending', 'in_transit'])) {
            session()->flash('error', __('Transfer cannot be approved. Current status: :status', ['status' => $transfer->status]));

            return;
        }

        // V25-CRIT-01 FIX: Create stock movements when approving transfer
        // Transfer completion should create two movements per item:
        // - transfer_out from from_warehouse_id
        // - transfer_in to to_warehouse_id
        DB::transaction(function () use ($transfer) {
            // V26-CRIT-02 FIX: Re-check status inside transaction with lock to handle race conditions
            $transfer = Transfer::lockForUpdate()->find($transfer->id);
            if (! in_array($transfer->status, ['pending', 'in_transit'])) {
                // Another request already processed this transfer
                return;
            }

            $stockMovementRepo = app(StockMovementRepositoryInterface::class);

            // Load items with product relationship
            $transfer->load('items.product');

            foreach ($transfer->items as $item) {
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                $qty = decimal_float($item->quantity, 4);

                // Skip items with zero or negative quantity
                if ($qty <= 0) {
                    continue;
                }

                // Create transfer_out movement from source warehouse
                $stockMovementRepo->create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'qty' => $qty,
                    'direction' => 'out',
                    'movement_type' => 'transfer_out',
                    'reference_type' => 'transfer',
                    'reference_id' => $transfer->id,
                    'notes' => "Transfer #{$transfer->reference_number} to warehouse ID {$transfer->to_warehouse_id}",
                    'unit_cost' => $item->unit_cost ?? 0,
                    // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                    'created_by' => actual_user_id(),
                ]);

                // Create transfer_in movement to destination warehouse
                $stockMovementRepo->create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'qty' => $qty,
                    'direction' => 'in',
                    'movement_type' => 'transfer_in',
                    'reference_type' => 'transfer',
                    'reference_id' => $transfer->id,
                    'notes' => "Transfer #{$transfer->reference_number} from warehouse ID {$transfer->from_warehouse_id}",
                    'unit_cost' => $item->unit_cost ?? 0,
                    // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                    'created_by' => actual_user_id(),
                ]);
            }

            // Update transfer status and timestamps
            $transfer->update([
                'status' => 'completed',
                'shipped_at' => now(),
                'received_at' => now(),
                // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                'received_by' => actual_user_id(),
            ]);
        });

        session()->flash('success', __('Transfer approved successfully'));
    }

    public function cancel(int $id): void
    {
        $this->authorize('warehouse.manage');

        $transfer = Transfer::findOrFail($id);

        // V25-MED-02 FIX: Super admins should be able to access all branches
        $user = auth()->user();
        if (! $user->hasRole('Super Admin') && $user->branch_id && $transfer->branch_id !== $user->branch_id) {
            abort(403);
        }

        $transfer->update(['status' => 'cancelled']);

        session()->flash('success', __('Transfer cancelled'));
    }

    public function delete(int $id): void
    {
        $this->authorize('warehouse.manage');

        $transfer = Transfer::findOrFail($id);

        // V25-MED-02 FIX: Super admins should be able to access all branches
        $user = auth()->user();
        if (! $user->hasRole('Super Admin') && $user->branch_id && $transfer->branch_id !== $user->branch_id) {
            abort(403);
        }

        $transfer->delete();

        session()->flash('success', __('Transfer deleted successfully'));
    }

    public function render()
    {
        $user = auth()->user();

        // V25-MED-02 FIX: Super admins should see all branches
        $shouldFilterByBranch = ! $user->hasRole('Super Admin') && $user->branch_id;

        $query = Transfer::with(['fromWarehouse', 'toWarehouse', 'items.product'])
            ->when($shouldFilterByBranch, fn ($q) => $q->where('transfers.branch_id', $user->branch_id))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    // V24-CRIT-02 FIX: Use 'notes' column (per migration) instead of 'note'
                    $query->where('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('fromWarehouse', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('toWarehouse', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection);

        $transfers = $query->paginate(15);

        // Statistics
        // V25-MED-02 FIX: Use shouldFilterByBranch consistently in all stats queries
        $stats = [
            'total' => Transfer::when($shouldFilterByBranch, fn ($q) => $q->where('branch_id', $user->branch_id))->count(),
            'pending' => Transfer::when($shouldFilterByBranch, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'pending')->count(),
            'completed' => Transfer::when($shouldFilterByBranch, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'completed')->count(),
            'cancelled' => Transfer::when($shouldFilterByBranch, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'cancelled')->count(),
        ];

        return view('livewire.warehouse.transfers.index', [
            'transfers' => $transfers,
            'stats' => $stats,
        ]);
    }
}
