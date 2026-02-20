<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Properties;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Property;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;

    public ?int $propertyId = null;

    public string $name = '';

    public string $address = '';

    public string $notes = '';

    public function mount(?int $property = null): void
    {
        if ($property) {
            $this->authorize('rental.properties.update');
            $this->propertyId = $property;
            $this->loadProperty();
        } else {
            $this->authorize('rental.properties.create');
        }
    }

    protected function loadProperty(): void
    {
        $property = Property::findOrFail($this->propertyId);

        $this->name = $property->name;
        $this->address = $property->address ?? '';
        $this->notes = $property->notes ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ];
    }

    public function save(): mixed
    {
        if ($this->propertyId) {
            $this->authorize('rental.properties.update');
        } else {
            $this->authorize('rental.properties.create');
        }

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
        ]);

        return $this->handleOperation(
            operation: function () use ($data, $branchId) {
                if ($this->propertyId) {
                    // Verify cross-branch access
                    $property = Property::findOrFail($this->propertyId);
                    if ($property->branch_id && $property->branch_id !== $branchId) {
                        abort(403, __('Cannot update property from another branch'));
                    }
                    $property->update($data);
                } else {
                    Property::create($data);
                }
                Cache::forget('properties_stats_'.$branchId);
            },
            successMessage: $this->propertyId
                ? __('Property updated successfully')
                : __('Property created successfully'),
            redirectRoute: 'app.rental.properties.index'
        );
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.rental.properties.form');
    }
}
