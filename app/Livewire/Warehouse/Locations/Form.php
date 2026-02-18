<?php

declare(strict_types=1);

namespace App\Livewire\Warehouse\Locations;

use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?int $warehouseId = null;

    public string $code = '';

    public string $name = '';

    public string $type = 'main';

    public string $status = 'active';

    public string $address = '';

    public string $notes = '';

    public function mount(?int $warehouse = null): void
    {
        $this->authorize('warehouse.manage');

        if ($warehouse) {
            $this->warehouseId = $warehouse;
            $this->loadWarehouse();
        }
    }

    protected function loadWarehouse(): void
    {
        $warehouse = Warehouse::findOrFail($this->warehouseId);

        $user = auth()->user();
        if ($user->branch_id && $warehouse->branch_id !== $user->branch_id) {
            abort(403);
        }

        $this->code = $warehouse->code;
        $this->name = $warehouse->name;
        $this->type = $warehouse->type ?? 'main';
        $this->status = $warehouse->status;
        $this->address = $warehouse->address ?? '';
        $this->notes = $warehouse->notes ?? '';
    }

    protected function rules(): array
    {
        $branchId = auth()->user()?->branch_id;

        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:main,secondary,virtual',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ];

        if ($this->warehouseId) {
            $rules['code'] = 'required|string|max:50|unique:warehouses,code,'.$this->warehouseId.',id'.($branchId ? ',branch_id,'.$branchId : '');
        } else {
            $rules['code'] = 'nullable|string|max:50|unique:warehouses,code,NULL,id'.($branchId ? ',branch_id,'.$branchId : '');
        }

        return $rules;
    }

    public function save(): mixed
    {
        $this->authorize('warehouse.manage');

        $validated = $this->validate();

        $user = auth()->user();
        $branchId = $user->branch_id;
        
        // Ensure branch context is available
        if (!$branchId) {
            session()->flash('error', __('Unable to determine branch for this operation'));
            return null;
        }
        
        $data = array_merge($validated, [
            'branch_id' => $branchId,
            'updated_by' => $user->id,
        ]);

        if ($this->warehouseId) {
            // Verify cross-branch access
            $warehouse = Warehouse::findOrFail($this->warehouseId);
            if ($warehouse->branch_id && $warehouse->branch_id !== $branchId) {
                abort(403, __('Cannot update warehouse from another branch'));
            }
            $warehouse->update($data);
            session()->flash('success', __('Warehouse updated successfully'));
        } else {
            $data['created_by'] = $user->id;
            Warehouse::create($data);
            session()->flash('success', __('Warehouse created successfully'));
        }

        Cache::forget('warehouses_stats_'.$branchId);

        return $this->redirectRoute('app.warehouse.locations.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.warehouse.locations.form');
    }
}
