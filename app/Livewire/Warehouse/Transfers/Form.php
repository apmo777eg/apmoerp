<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Transfers;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;
    use HasMultilingualValidation;

    public ?Transfer $transfer = null;

    public ?int $transferId = null;

    public ?int $fromWarehouseId = null;

    public ?int $toWarehouseId = null;

    public string $status = 'pending';

    public string $note = '';

    public array $items = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->authorize('warehouse.manage');
            $this->transferId = $id;
            $this->transfer = Transfer::with('items.product')->findOrFail($id);

            $user = auth()->user();
            if ($user->branch_id && $this->transfer->branch_id !== $user->branch_id) {
                abort(403);
            }

            $this->loadTransfer();
        } else {
            $this->authorize('warehouse.manage');
            $this->items = [['product_id' => null, 'qty' => 0]];
        }
    }

    protected function loadTransfer(): void
    {
        $this->fromWarehouseId = $this->transfer->from_warehouse_id;
        $this->toWarehouseId = $this->transfer->to_warehouse_id;
        $this->status = $this->transfer->status;
        // V23-HIGH-05 FIX: Use 'notes' field (with backward compat accessor 'note')
        $this->note = $this->transfer->notes ?? '';

        $this->items = $this->transfer->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'qty' => $item->qty,
            ];
        })->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'qty' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): mixed
    {
        $this->authorize('warehouse.manage');

        $this->validate([
            'fromWarehouseId' => 'required|exists:warehouses,id',
            'toWarehouseId' => 'required|exists:warehouses,id|different:fromWarehouseId',
            'status' => 'required|in:pending,in_transit,completed,cancelled',
            'note' => $this->unicodeText(required: false),
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
        ], [
            'toWarehouseId.different' => 'Destination warehouse must be different from source warehouse',
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
        ]);

        $user = auth()->user();

        // NEW-V15-HIGH-02 FIX: Do not default branch_id to 1
        // Require explicit branch selection when user has no branch_id
        if ($user->branch_id === null) {
            $this->addError('branch_id', __('Branch selection is required. Please select a branch.'));

            return null;
        }

        // V23-HIGH-05 FIX: Use 'notes' column (per migration) instead of 'note'
        // and don't overwrite created_by on updates
        $data = [
            'branch_id' => $user->branch_id,
            'from_warehouse_id' => $this->fromWarehouseId,
            'to_warehouse_id' => $this->toWarehouseId,
            'status' => $this->status,
            'notes' => $this->note,
        ];

        if ($this->transfer) {
            // Don't overwrite created_by on updates
            $this->transfer->update($data);
        } else {
            // Only set created_by on create
            $data['created_by'] = $user->id;
            $this->transfer = Transfer::create($data);
        }

        // Save items
        $this->transfer->items()->delete();

        foreach ($this->items as $item) {
            // V24-HIGH-01 FIX: Fetch product to get unit_cost for proper transfer value calculation
            $product = Product::find($item['product_id']);
            $unitCost = $product ? ($product->cost ?? $product->standard_cost ?? 0) : 0;
            
            TransferItem::create([
                'transfer_id' => $this->transfer->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['qty'], // Use quantity column per migration
                'unit_cost' => $unitCost,
            ]);
        }
        
        // V24-HIGH-01 FIX: Update transfer total_value after items are saved
        $this->transfer->updateTotalValue();

        session()->flash('success', __('Transfer saved successfully'));

        $this->redirectRoute('app.warehouse.transfers.index', navigate: true);
    }

    public function render()
    {
        $user = auth()->user();

        $warehouses = Warehouse::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('livewire.warehouse.transfers.form', [
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }
}
