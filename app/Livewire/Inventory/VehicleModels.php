<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\VehicleModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class VehicleModels extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $brandFilter = '';

    public function mount(): void
    {
        $this->authorize('spares.compatibility.manage');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBrandFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('spares.compatibility.manage');
        VehicleModel::where('id', $id)->delete();
        session()->flash('status', __('Vehicle model deleted successfully.'));
    }

    public function toggleActive(int $id): void
    {
        // V57-CRITICAL-04 FIX: Re-authorize on mutation to prevent IDOR attacks
        $this->authorize('spares.compatibility.manage');
        
        $model = VehicleModel::findOrFail($id);
        $model->is_active = ! $model->is_active;
        $model->save();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $query = VehicleModel::query()
            ->withCount('compatibilities');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('brand', 'like', "%{$this->search}%")
                    ->orWhere('model', 'like', "%{$this->search}%");
            });
        }

        if ($this->brandFilter) {
            $query->where('brand', $this->brandFilter);
        }

        $brands = VehicleModel::distinct()->pluck('brand')->sort()->values();

        return view('livewire.inventory.vehicle-models', [
            'models' => $query->orderBy('brand')->orderBy('model')->paginate(15),
            'brands' => $brands,
        ]);
    }
}
