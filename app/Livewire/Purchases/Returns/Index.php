<?php

declare(strict_types=1);

namespace App\Livewire\Purchases\Returns;

use App\Models\Purchase;
use App\Models\ReturnNote;
use App\Models\User;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public int $perPage = 15;

    public bool $showReturnModal = false;

    public ?int $selectedPurchaseId = null;

    public ?Purchase $selectedPurchase = null;

    public array $returnItems = [];

    public string $returnReason = '';

    protected function getUserBranchId(): ?int
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->branch_id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openReturnModal(?int $purchaseId = null): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->can('purchases.return')) {
            $this->dispatch('notify', type: 'error', message: __('Unauthorized'));

            return;
        }

        $this->selectedPurchaseId = $purchaseId;
        $this->selectedPurchase = null;
        $this->returnItems = [];
        $this->returnReason = '';

        if ($purchaseId) {
            $this->loadPurchase();
        }

        $this->showReturnModal = true;
    }

    public function loadPurchase(): void
    {
        if (! $this->selectedPurchaseId) {
            return;
        }

        $branchId = $this->getUserBranchId();

        $this->selectedPurchase = Purchase::with(['items.product', 'supplier'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('id', $this->selectedPurchaseId)
            ->whereNotIn('status', ['returned', 'cancelled'])
            ->first();

        if (! $this->selectedPurchase) {
            $this->dispatch('notify', type: 'error', message: __('Purchase not found or already returned'));
            $this->selectedPurchaseId = null;

            return;
        }

        $this->returnItems = $this->selectedPurchase->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name ?? 'Unknown',
            'max_qty' => decimal_float($item->qty, 4),
            'qty' => 0,
            'cost' => decimal_float($item->unit_cost, 4),
        ])->toArray();
    }

    public function processReturn(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->can('purchases.return')) {
            $this->dispatch('notify', type: 'error', message: __('Unauthorized'));

            return;
        }

        if (! $this->selectedPurchaseId || ! $this->selectedPurchase) {
            $this->dispatch('notify', type: 'error', message: __('Please select a valid purchase'));

            return;
        }

        $branchId = $this->getUserBranchId();
        $purchase = Purchase::query()
            ->with('items')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('id', $this->selectedPurchaseId)
            ->whereNotIn('status', ['returned', 'cancelled'])
            ->first();

        if (! $purchase) {
            $this->dispatch('notify', type: 'error', message: __('Purchase not found or already processed'));

            return;
        }

        $itemsToReturn = collect($this->returnItems)
            ->filter(fn ($item) => $item['qty'] > 0)
            ->values()
            ->toArray();

        if (empty($itemsToReturn)) {
            $this->dispatch('notify', type: 'error', message: __('Please select at least one item to return'));

            return;
        }

        try {
            DB::transaction(function () use ($itemsToReturn, $user, $purchase) {
                $stockMovementRepo = app(StockMovementRepositoryInterface::class);
                $refund = 0.0;
                $processedItems = [];

                foreach ($itemsToReturn as $it) {
                    $pi = $purchase->items->firstWhere('product_id', $it['product_id']);
                    if (! $pi) {
                        continue;
                    }
                    // V49-CRIT-01 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for quantities and costs
                    $qty = min(decimal_float($it['qty'], 4), decimal_float($pi->qty, 4));
                    // V23-CRIT-01 FIX: Use unit_cost accessor (maps to unit_price) instead of non-existent cost
                    $unitCost = decimal_float($pi->unit_cost, 4);
                    $line = $qty * $unitCost;
                    $refund += $line;

                    // V25-CRIT-02 FIX: Track processed items for stock movements
                    $processedItems[] = [
                        'product_id' => $pi->product_id,
                        'qty' => $qty,
                        'unit_cost' => $unitCost,
                    ];
                }

                // V25-CRIT-02 FIX: Use correct ReturnNote fields per model schema
                // Include warehouse_id for proper inventory tracking
                $returnNote = ReturnNote::create([
                    'branch_id' => $purchase->branch_id,
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'warehouse_id' => $purchase->warehouse_id,
                    'type' => ReturnNote::TYPE_PURCHASE,
                    'status' => ReturnNote::STATUS_PENDING,
                    'return_date' => now(),
                    'reason' => $this->returnReason ?: null,
                    'total_amount' => $refund,
                    'processed_by' => $user->id,
                ]);

                // V25-CRIT-02 FIX: Create stock movements to deduct returned items from inventory
                // Purchase returns reduce inventory (items going back to supplier)
                if ($purchase->warehouse_id) {
                    foreach ($processedItems as $item) {
                        $stockMovementRepo->create([
                            'product_id' => $item['product_id'],
                            'warehouse_id' => $purchase->warehouse_id,
                            'qty' => $item['qty'],
                            'direction' => 'out',
                            'movement_type' => 'purchase_return',
                            'reference_type' => 'return_note',
                            'reference_id' => $returnNote->id,
                            'notes' => "Purchase return for Purchase #{$purchase->reference_number}",
                            'unit_cost' => $item['unit_cost'],
                            'created_by' => $user->id,
                        ]);
                    }
                }

                $purchase->status = 'returned';
                $purchase->save();
            });

            $this->showReturnModal = false;
            $this->selectedPurchase = null;
            $this->selectedPurchaseId = null;
            $this->returnItems = [];
            $this->returnReason = '';
            $this->dispatch('notify', type: 'success', message: __('Purchase return processed successfully'));
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: __('Failed to process return. Please try again.'));
        }
    }

    public function closeReturnModal(): void
    {
        $this->showReturnModal = false;
        $this->selectedPurchase = null;
        $this->selectedPurchaseId = null;
        $this->returnItems = [];
        $this->returnReason = '';
    }

    public function deleteReturn(int $id): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->can('purchases.return')) {
            $this->dispatch('notify', type: 'error', message: __('Unauthorized'));

            return;
        }

        $branchId = $this->getUserBranchId();
        $returnNote = ReturnNote::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('id', $id)
            ->first();

        if ($returnNote) {
            $returnNote->delete();
            $this->dispatch('notify', type: 'success', message: __('Return note deleted'));
        } else {
            $this->dispatch('notify', type: 'error', message: __('Return note not found'));
        }
    }

    public function render(): View
    {
        $branchId = $this->getUserBranchId();

        $returns = ReturnNote::query()
            ->with(['purchase.supplier'])
            ->whereNotNull('purchase_id')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, function ($query) {
                $query->whereHas('purchase', function ($q) {
                    $q->where('reference_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        // Only load purchases for the return modal (lazy load)
        $purchases = collect();

        return view('livewire.purchases.returns.index', [
            'returns' => $returns,
            'purchases' => $purchases,
        ]);
    }
}
