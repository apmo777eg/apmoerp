<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing\ProductionOrders;

use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?ProductionOrder $productionOrder = null;

    public bool $editMode = false;

    public ?int $bom_id = null;

    public ?int $product_id = null;

    public ?int $warehouse_id = null;

    public float $quantity_planned = 1.0;

    public string $status = 'draft';

    public string $priority = 'normal';

    public ?string $planned_start_date = null;

    public ?string $planned_end_date = null;

    public string $notes = '';

    protected function rules(): array
    {
        $branchId = auth()->user()?->branch_id;

        return [
            // V58-CRITICAL-02 FIX: Use BranchScopedExists for branch-aware validation
            'bom_id' => ['required', new \App\Rules\BranchScopedExists('bills_of_materials', 'id', $branchId)],
            'product_id' => ['required', new \App\Rules\BranchScopedExists('products', 'id', $branchId)],
            'warehouse_id' => ['required', new \App\Rules\BranchScopedExists('warehouses', 'id', $branchId)],
            'quantity_planned' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', 'in:draft,planned,released,in_progress,completed,cancelled'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function mount(?ProductionOrder $productionOrder = null): void
    {
        if ($productionOrder && $productionOrder->exists) {
            $this->authorize('manufacturing.edit');
            $this->productionOrder = $productionOrder;
            $this->editMode = true;
            $this->fillFormFromModel();
        } else {
            $this->authorize('manufacturing.create');
        }
    }

    protected function fillFormFromModel(): void
    {
        $this->bom_id = $this->productionOrder->bom_id;
        $this->product_id = $this->productionOrder->product_id;
        $this->warehouse_id = $this->productionOrder->warehouse_id;
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $this->quantity_planned = decimal_float($this->productionOrder->quantity_planned, 4);
        $this->status = $this->productionOrder->status;
        $this->priority = $this->productionOrder->priority;
        $this->planned_start_date = $this->productionOrder->planned_start_date?->format('Y-m-d');
        $this->planned_end_date = $this->productionOrder->planned_end_date?->format('Y-m-d');
        $this->notes = $this->productionOrder->notes ?? '';
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('manufacturing.orders.manage');

        $this->validate();

        $user = auth()->user();
        $branchId = $user->branch_id;

        // V32-HIGH-A02 FIX: Don't fallback to Branch::first() as it may assign records to wrong branch
        // If user has no branch assigned, they should not be able to create records
        if (! $branchId) {
            session()->flash('error', __('No branch assigned to your account. Please contact your administrator.'));

            return null;
        }

        $data = [
            'branch_id' => $branchId,
            'bom_id' => $this->bom_id,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity_planned' => $this->quantity_planned,
            'status' => $this->status,
            'priority' => $this->priority,
            'planned_start_date' => $this->planned_start_date,
            'planned_end_date' => $this->planned_end_date,
            'notes' => $this->notes,
        ];

        if ($this->editMode) {
            // V23-HIGH-08 FIX: Don't overwrite created_by on updates
            $this->productionOrder->update($data);
            session()->flash('success', __('Production Order updated successfully.'));
        } else {
            // Only set created_by on create
            $data['created_by'] = $user->id;
            $data['order_number'] = ProductionOrder::generateOrderNumber($branchId);
            ProductionOrder::create($data);
            session()->flash('success', __('Production Order created successfully.'));
        }

        $this->redirectRoute('app.manufacturing.orders.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();
        $branchId = $user->branch_id ?? null;

        // V23-HIGH-08 FIX: Handle branch-less users properly
        // Don't use 'where branch_id = null' which returns nothing
        $boms = BillOfMaterial::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->with('product')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $warehouses = Warehouse::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.manufacturing.production-orders.form', [
            'boms' => $boms,
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }
}
