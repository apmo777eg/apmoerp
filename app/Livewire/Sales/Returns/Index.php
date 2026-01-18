<?php

declare(strict_types=1);

namespace App\Livewire\Sales\Returns;

use App\Models\ReturnNote;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public int $perPage = 15;

    public bool $showReturnModal = false;

    public ?int $selectedSaleId = null;

    public ?Sale $selectedSale = null;

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

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openReturnModal(?int $saleId = null): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->can('sales.return')) {
            $this->dispatch('notify', type: 'error', message: __('Unauthorized'));

            return;
        }

        $this->selectedSaleId = $saleId;
        $this->selectedSale = null;
        $this->returnItems = [];
        $this->returnReason = '';

        if ($saleId) {
            $this->loadSale();
        }

        $this->showReturnModal = true;
    }

    public function loadSale(): void
    {
        if (! $this->selectedSaleId) {
            return;
        }

        $branchId = $this->getUserBranchId();

        $this->selectedSale = Sale::with(['items.product', 'customer'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('id', $this->selectedSaleId)
            ->whereNotIn('status', ['returned', 'cancelled'])
            ->first();

        if (! $this->selectedSale) {
            $this->dispatch('notify', type: 'error', message: __('Sale not found or already returned'));
            $this->selectedSaleId = null;

            return;
        }

        $existingReturnProductIds = ReturnNote::query()
            ->where('sale_id', $this->selectedSaleId)
            ->pluck('id')
            ->toArray();

        $this->returnItems = $this->selectedSale->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name ?? 'Unknown',
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            'max_qty' => decimal_float($item->qty),
            'qty' => 0,
            'price' => decimal_float($item->unit_price),
        ])->toArray();
    }

    public function processReturn(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->can('sales.return')) {
            $this->dispatch('notify', type: 'error', message: __('Unauthorized'));

            return;
        }

        if (! $this->selectedSaleId || ! $this->selectedSale) {
            $this->dispatch('notify', type: 'error', message: __('Please select a valid sale'));

            return;
        }

        $branchId = $this->getUserBranchId();
        $sale = Sale::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('id', $this->selectedSaleId)
            ->whereNotIn('status', ['returned', 'cancelled'])
            ->first();

        if (! $sale) {
            $this->dispatch('notify', type: 'error', message: __('Sale not found or already processed'));

            return;
        }

        $itemsToReturn = collect($this->returnItems)
            ->filter(fn ($item) => $item['qty'] > 0)
            ->map(fn ($item) => [
                'product_id' => $item['product_id'],
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                'qty' => min(decimal_float($item['qty']), decimal_float($item['max_qty'])),
            ])
            ->values()
            ->toArray();

        if (empty($itemsToReturn)) {
            $this->dispatch('notify', type: 'error', message: __('Please select at least one item to return'));

            return;
        }

        try {
            $service = app(SaleService::class);
            $returnNote = $service->handleReturn(
                $this->selectedSaleId,
                $itemsToReturn,
                $this->returnReason ?: null
            );

            $this->showReturnModal = false;
            $this->selectedSale = null;
            $this->selectedSaleId = null;
            $this->returnItems = [];
            $this->returnReason = '';
            $this->dispatch('notify', type: 'success', message: __('Return processed successfully'));
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: __('Failed to process return. Please try again.'));
        }
    }

    public function closeReturnModal(): void
    {
        $this->showReturnModal = false;
        $this->selectedSale = null;
        $this->selectedSaleId = null;
        $this->returnItems = [];
        $this->returnReason = '';
    }

    public function deleteReturn(int $id): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->can('sales.return')) {
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
            ->with(['sale.customer'])
            ->whereNotNull('sale_id')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, function ($query) {
                // V21-HIGH-03 Fix: Use reference_number instead of code column
                // The 'code' column doesn't exist in the sales table
                $query->whereHas('sale', function ($q) {
                    $q->where('reference_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $sales = Sale::query()
            ->with('customer')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['returned', 'cancelled'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('livewire.sales.returns.index', [
            'returns' => $returns,
            'sales' => $sales,
        ]);
    }
}
