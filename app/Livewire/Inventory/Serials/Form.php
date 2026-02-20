<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Serials;

use App\Models\InventoryBatch;
use App\Models\InventorySerial;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?InventorySerial $serial = null;

    public bool $isEditing = false;

    // Form fields
    public ?int $product_id = null;

    public ?int $warehouse_id = null;

    public ?int $batch_id = null;

    public string $serial_number = '';

    public string $unit_cost = '';

    public string $warranty_start = '';

    public string $warranty_end = '';

    public string $notes = '';

    protected function rules(): array
    {
        $uniqueRule = 'unique:inventory_serials,serial_number';
        if ($this->isEditing && $this->serial) {
            $uniqueRule .= ','.$this->serial->id;
        }

        // Resolve the effective branch for validation.
        // - Editing: use the existing serial branch
        // - Creating: use the current branch context
        $branchId = (int) ($this->serial?->branch_id ?? current_branch_id() ?? 0);
        $branchId = $branchId > 0 ? $branchId : null;

        return [
            // V58-CRITICAL-02 FIX: Use BranchScopedExists for branch-aware validation
            'product_id' => ['required', new \App\Rules\BranchScopedExists('products', 'id', $branchId)],
            'warehouse_id' => ['nullable', new \App\Rules\BranchScopedExists('warehouses', 'id', $branchId, allowNull: true)],
            'batch_id' => ['nullable', new \App\Rules\BranchScopedExists('inventory_batches', 'id', $branchId, allowNull: true)],
            'serial_number' => ['required', 'string', 'max:255', $uniqueRule],
            'unit_cost' => 'required|numeric|min:0',
            'warranty_start' => 'nullable|date',
            'warranty_end' => 'nullable|date|after:warranty_start',
            'notes' => 'nullable|string',
        ];
    }

    public function mount(?InventorySerial $serial = null): void
    {
        $this->authorize('inventory.products.view');

        if ($serial && $serial->exists) {
            $this->isEditing = true;
            $this->serial = $serial;
            $this->fill($serial->toArray());
            $this->warranty_start = $serial->warranty_start?->format('Y-m-d') ?? '';
            $this->warranty_end = $serial->warranty_end?->format('Y-m-d') ?? '';
        } else {
            $this->serial_number = 'SN-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6));
        }
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('inventory.products.manage');

        $this->validate();

        $branchId = (int) ($this->serial?->branch_id ?? current_branch_id() ?? 0);

        if ($branchId <= 0) {
            $this->addError('branch_id', __('Please select a branch first.'));
            return null;
        }

        $data = [
            'branch_id' => $branchId,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'batch_id' => $this->batch_id,
            'serial_number' => $this->serial_number,
            'unit_cost' => $this->unit_cost,
            'warranty_start' => $this->warranty_start ?: null,
            'warranty_end' => $this->warranty_end ?: null,
            'notes' => $this->notes,
            'status' => 'in_stock',
        ];

        if ($this->isEditing) {
            $this->serial->update($data);
            session()->flash('success', __('Serial number updated successfully'));
        } else {
            InventorySerial::create($data);
            session()->flash('success', __('Serial number created successfully'));
        }

        $this->redirectRoute('app.inventory.serials.index', navigate: true);
    }

    public function render()
    {
        $branchId = (int) ($this->serial?->branch_id ?? current_branch_id() ?? 0);

        $products = Product::query()
            ->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereRaw('1=0'))
            ->where('is_serialized', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::query()
            ->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereRaw('1=0'))
            ->orderBy('name')
            ->get();

        $batches = [];
        if ($branchId > 0 && $this->product_id) {
            $batches = InventoryBatch::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $this->product_id)
                ->where('status', 'active')
                ->orderBy('batch_number')
                ->get();
        }

        return view('livewire.inventory.serials.form', [
            'products' => $products,
            'warehouses' => $warehouses,
            'batches' => $batches,
        ]);
    }
}
