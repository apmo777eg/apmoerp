<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\ProductCompatibility as ProductCompatibilityModel;
use App\Models\VehicleModel;
use App\Services\SparePartsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class ProductCompatibility extends Component
{
    use WithPagination;

    public ?int $productId = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $brandFilter = '';

    public ?int $editingId = null;

    public string $newBrand = '';

    public string $newModel = '';

    public ?int $newYearFrom = null;

    public ?int $newYearTo = null;

    public string $newCategory = '';

    public string $newEngineType = '';

    public string $selectedVehicleId = '';

    public string $oemNumber = '';

    public string $position = '';

    public string $notes = '';

    public bool $isVerified = false;

    public bool $showVehicleForm = false;

    public bool $showCompatibilityForm = false;

    protected SparePartsService $sparePartsService;

    public function boot(SparePartsService $sparePartsService): void
    {
        $this->sparePartsService = $sparePartsService;
    }

    #[On('refreshComponent')]
    public function refreshComponent(): void
    {
        // Livewire 4 compatible refresh handler
    }

    public function mount($product = null): void
    {
        if ($product instanceof \App\Models\Product) {
            $this->productId = $product->id;
        } elseif (is_numeric($product)) {
            $this->productId = (int) $product;
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $brands = $this->sparePartsService->getBrands();

        $vehicleModelsQuery = VehicleModel::query()
            ->when($this->search, fn ($q) => $q->where('model', 'like', "%{$this->search}%")
                ->orWhere('brand', 'like', "%{$this->search}%"))
            ->when($this->brandFilter, fn ($q) => $q->where('brand', $this->brandFilter))
            ->orderBy('brand')
            ->orderBy('model');

        $vehicleModels = $vehicleModelsQuery->paginate(15);

        $compatibilities = $this->productId
            ? ProductCompatibilityModel::with('vehicleModel')
                ->where('product_id', $this->productId)
                ->get()
            : collect();

        $product = $this->productId ? Product::find($this->productId) : null;

        return view('livewire.inventory.product-compatibility', [
            'brands' => $brands,
            'vehicleModels' => $vehicleModels,
            'compatibilities' => $compatibilities,
            'product' => $product,
        ]);
    }

    public function openVehicleForm(): void
    {
        $this->resetVehicleForm();
        $this->showVehicleForm = true;
    }

    public function closeVehicleForm(): void
    {
        $this->showVehicleForm = false;
        $this->resetVehicleForm();
    }

    public function resetVehicleForm(): void
    {
        $this->editingId = null;
        $this->newBrand = '';
        $this->newModel = '';
        $this->newYearFrom = null;
        $this->newYearTo = null;
        $this->newCategory = '';
        $this->newEngineType = '';
    }

    public function editVehicle(int $id): void
    {
        $vehicle = VehicleModel::find($id);
        if ($vehicle) {
            $this->editingId = $id;
            $this->newBrand = $vehicle->brand;
            $this->newModel = $vehicle->model;
            $this->newYearFrom = $vehicle->year_from;
            $this->newYearTo = $vehicle->year_to;
            $this->newCategory = $vehicle->category ?? '';
            $this->newEngineType = $vehicle->engine_type ?? '';
            $this->showVehicleForm = true;
        }
    }

    public function saveVehicle(): void
    {
        $this->validate([
            'newBrand' => 'required|string|max:100',
            'newModel' => 'required|string|max:100',
            'newYearFrom' => 'nullable|integer|min:1900|max:2100',
            'newYearTo' => 'nullable|integer|min:1900|max:2100|gte:newYearFrom',
        ]);

        $data = [
            'brand' => $this->newBrand,
            'model' => $this->newModel,
            'year_from' => $this->newYearFrom,
            'year_to' => $this->newYearTo,
            'category' => $this->newCategory ?: null,
            'engine_type' => $this->newEngineType ?: null,
            'is_active' => true,
        ];

        if ($this->editingId) {
            $this->sparePartsService->updateVehicleModel($this->editingId, $data);
            $this->dispatch('notify', type: 'success', message: __('Vehicle model updated successfully'));
        } else {
            $this->sparePartsService->createVehicleModel($data);
            $this->dispatch('notify', type: 'success', message: __('Vehicle model created successfully'));
        }

        $this->closeVehicleForm();
    }

    public function deleteVehicle(int $id): void
    {
        $this->sparePartsService->deleteVehicleModel($id);
        $this->dispatch('notify', type: 'success', message: __('Vehicle model deleted successfully'));
    }

    public function openCompatibilityForm(): void
    {
        $this->resetCompatibilityForm();
        $this->showCompatibilityForm = true;
    }

    public function closeCompatibilityForm(): void
    {
        $this->showCompatibilityForm = false;
        $this->resetCompatibilityForm();
    }

    public function resetCompatibilityForm(): void
    {
        $this->selectedVehicleId = '';
        $this->oemNumber = '';
        $this->position = '';
        $this->notes = '';
        $this->isVerified = false;
    }

    public function addCompatibility(): void
    {
        if (! $this->productId || ! $this->selectedVehicleId) {
            $this->dispatch('notify', type: 'error', message: __('Please select a vehicle model'));

            return;
        }

        $this->sparePartsService->addCompatibility(
            $this->productId,
            (int) $this->selectedVehicleId,
            [
                'oem_number' => $this->oemNumber ?: null,
                'position' => $this->position ?: null,
                'notes' => $this->notes ?: null,
                'is_verified' => $this->isVerified,
            ]
        );

        $this->dispatch('notify', type: 'success', message: __('Compatibility added successfully'));
        $this->closeCompatibilityForm();
    }

    public function quickAddCompatibility(int $vehicleModelId): void
    {
        if (! $this->productId) {
            $this->dispatch('notify', type: 'error', message: __('No product selected'));

            return;
        }

        $this->sparePartsService->addCompatibility($this->productId, $vehicleModelId);
        $this->dispatch('notify', type: 'success', message: __('Compatibility added successfully'));
    }

    public function removeCompatibility(int $vehicleModelId): void
    {
        if (! $this->productId) {
            return;
        }

        $this->sparePartsService->removeCompatibility($this->productId, $vehicleModelId);
        $this->dispatch('notify', type: 'success', message: __('Compatibility removed successfully'));
    }

    public function toggleVerified(int $compatibilityId): void
    {
        $compatibility = ProductCompatibilityModel::find($compatibilityId);
        if ($compatibility) {
            $compatibility->is_verified = ! $compatibility->is_verified;
            $compatibility->save();
        }
    }
}
