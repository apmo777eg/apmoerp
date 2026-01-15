<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Adjustments;

use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Adjustment $adjustment = null;

    public ?int $adjustmentId = null;

    public ?int $warehouseId = null;

    public string $reason = '';

    public string $note = '';

    public array $items = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->authorize('warehouse.manage');
            $this->adjustmentId = $id;
            $this->adjustment = Adjustment::with('items.product')->findOrFail($id);

            // Check branch access
            $user = auth()->user();
            if ($user->branch_id && $this->adjustment->branch_id !== $user->branch_id) {
                abort(403, 'Unauthorized access to this branch data');
            }

            $this->loadAdjustment();
        } else {
            $this->authorize('warehouse.manage');
            // Initialize with empty item
            $this->items = [['product_id' => null, 'qty' => 0]];
        }
    }

    protected function loadAdjustment(): void
    {
        $this->warehouseId = $this->adjustment->warehouse_id;
        $this->reason = $this->adjustment->reason;
        $this->note = $this->adjustment->note ?? '';

        $this->items = $this->adjustment->items->map(function ($item) {
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
        $this->items = array_values($this->items); // Re-index array
    }

    public function save(): mixed
    {
        $this->authorize('warehouse.manage');

        $this->validate([
            'warehouseId' => 'required|exists:warehouses,id',
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|not_in:0',
        ], [
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            'items.*.product_id.required' => 'Product is required',
            'items.*.qty.required' => 'Quantity is required',
            'items.*.qty.not_in' => 'Quantity cannot be zero',
        ]);

        $user = auth()->user();
        $warehouse = Warehouse::findOrFail($this->warehouseId);

        if ($user->branch_id && $warehouse->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized access to this warehouse.');
        }

        $branchId = $warehouse->branch_id;
        $inventory = app(InventoryService::class);

        DB::transaction(function () use ($user, $warehouse, $branchId, $inventory) {
            $data = [
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->id,
                'reason' => $this->reason,
            ];

            if ($this->adjustment) {
                // V23-MED-05 FIX: Don't overwrite created_by on updates
                $this->adjustment->update($data);
            } else {
                // Only set created_by on create
                $data['created_by'] = $user->id;
                $this->adjustment = Adjustment::create($data);
            }

            // V23-MED-03 FIX: Save note field using accessor (maps to reason)
            // since 'note' is not in fillable but has setNoteAttribute accessor
            // Only append if note is provided and different from reason
            if ($this->note && $this->note !== $this->reason) {
                // Append note to reason if both are provided
                $combinedReason = $this->reason.' - '.$this->note;
                $this->adjustment->reason = $combinedReason;
                $this->adjustment->save();
            }

            $existingItemIds = $this->adjustment->items()->pluck('id');
            if ($existingItemIds->isNotEmpty()) {
                StockMovement::where('reference_type', AdjustmentItem::class)
                    ->whereIn('reference_id', $existingItemIds)
                    ->delete();
            }

            $this->adjustment->items()->delete();

            foreach ($this->items as $item) {
                $product = Product::where('branch_id', $branchId)->findOrFail($item['product_id']);

                $adjustmentItem = AdjustmentItem::create([
                    'adjustment_id' => $this->adjustment->id,
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                ]);

                $inventory->recordStockAdjustment([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'branch_id' => $branchId,
                    'reference_type' => AdjustmentItem::class,
                    'reference_id' => $adjustmentItem->id,
                    'qty' => abs((float) $item['qty']),
                    'direction' => (float) $item['qty'] >= 0 ? 'in' : 'out',
                    'reason' => $this->reason,
                    'extra_attributes' => [
                        'adjustment_id' => $this->adjustment->id,
                        'adjustment_item_id' => $adjustmentItem->id,
                    ],
                ]);
            }
        });

        session()->flash('success', __('Adjustment saved successfully'));

        $this->redirectRoute('app.warehouse.adjustments.index', navigate: true);
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

        return view('livewire.warehouse.adjustments.form', [
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }
}
