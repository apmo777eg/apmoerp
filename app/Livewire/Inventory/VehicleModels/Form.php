<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\VehicleModels;

use App\Models\VehicleModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use AuthorizesRequests;

    public ?int $vehicleModelId = null;

    public string $brand = '';

    public string $model = '';

    public ?int $year_from = null;

    public ?int $year_to = null;

    public string $engine_type = '';

    public string $category = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:year_from'],
            'engine_type' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function mount(?int $vehicleModel = null): void
    {
        $this->authorize('spares.compatibility.manage');

        if ($vehicleModel) {
            $model = VehicleModel::findOrFail($vehicleModel);
            $this->vehicleModelId = $model->id;
            $this->brand = $model->brand;
            $this->model = $model->model;
            $this->year_from = $model->year_from;
            $this->year_to = $model->year_to;
            $this->engine_type = $model->engine_type ?? '';
            $this->category = $model->category ?? '';
            $this->is_active = (bool) $model->is_active;
        }
    }

    public function save(): mixed
    {
        $this->authorize('spares.compatibility.manage');
        $this->validate();

        $data = [
            'brand' => $this->brand,
            'model' => $this->model,
            'year_from' => $this->year_from ?: null,
            'year_to' => $this->year_to ?: null,
            'engine_type' => $this->engine_type ?: null,
            'category' => $this->category ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->vehicleModelId) {
            VehicleModel::where('id', $this->vehicleModelId)->update($data);
            session()->flash('status', __('Vehicle model updated successfully.'));
        } else {
            VehicleModel::create($data);
            session()->flash('status', __('Vehicle model created successfully.'));
        }

        $this->redirectRoute('app.inventory.vehicle-models.index', navigate: true);
    }

    public function cancel(): void
    {
        $this->redirectRoute('app.inventory.vehicle-models.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.inventory.vehicle-models.form');
    }
}
