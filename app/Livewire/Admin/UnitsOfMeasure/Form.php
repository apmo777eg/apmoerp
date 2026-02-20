<?php

declare(strict_types=1);

namespace App\Livewire\Admin\UnitsOfMeasure;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use HasMultilingualValidation;

    public ?int $unitId = null;

    public string $name = '';

    public string $nameAr = '';

    public string $symbol = '';

    public string $type = 'unit';

    public ?int $baseUnitId = null;

    public float $conversionFactor = 1;

    public int $decimalPlaces = 2;

    public bool $isBaseUnit = false;

    public bool $isActive = true;

    public int $sortOrder = 0;

    public function mount(?int $unit = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.units.manage')) {
            abort(403);
        }

        if ($unit) {
            $this->unitId = $unit;
            $this->loadUnit();
        } else {
            $this->sortOrder = UnitOfMeasure::max('sort_order') + 1;
        }
    }

    protected function loadUnit(): void
    {
        $unit = UnitOfMeasure::findOrFail($this->unitId);
        $this->name = $unit->name;
        $this->nameAr = $unit->name_ar ?? '';
        $this->symbol = $unit->symbol;
        $this->type = $unit->type;
        $this->baseUnitId = $unit->base_unit_id;
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $this->conversionFactor = decimal_float($unit->conversion_factor, 4);
        $this->decimalPlaces = $unit->decimal_places;
        $this->isBaseUnit = $unit->is_base_unit;
        $this->isActive = $unit->is_active;
        $this->sortOrder = $unit->sort_order;
    }

    public function updatedIsBaseUnit(): void
    {
        if ($this->isBaseUnit) {
            $this->baseUnitId = null;
            $this->conversionFactor = 1;
        }
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $this->unitId
                    ? Rule::unique('units_of_measure', 'name')->ignore($this->unitId)
                    : Rule::unique('units_of_measure', 'name'),
            ],
            'nameAr' => 'nullable|string|max:255',
            'symbol' => [
                'required',
                'string',
                'max:20',
                $this->unitId
                    ? Rule::unique('units_of_measure', 'symbol')->ignore($this->unitId)
                    : Rule::unique('units_of_measure', 'symbol'),
            ],
            'type' => 'required|string|in:unit,weight,length,volume,area,time,other',
            'baseUnitId' => 'nullable|exists:units_of_measure,id',
            'conversionFactor' => 'required|numeric|min:0.000001',
            'decimalPlaces' => 'integer|min:0|max:6',
            'sortOrder' => 'integer|min:0',
        ];
    }

    public function save(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.units.manage')) {
            abort(403);
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'name_ar' => $this->nameAr ?: null,
            'symbol' => $this->symbol,
            'type' => $this->type,
            'base_unit_id' => $this->isBaseUnit ? null : $this->baseUnitId,
            'conversion_factor' => $this->isBaseUnit ? 1 : $this->conversionFactor,
            'decimal_places' => $this->decimalPlaces,
            'is_base_unit' => $this->isBaseUnit,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
            'updated_by' => $user?->id,
        ];

        try {
            if ($this->unitId) {
                $unit = UnitOfMeasure::findOrFail($this->unitId);
                $unit->update($data);
                session()->flash('success', __('Unit updated successfully'));
            } else {
                $data['created_by'] = $user?->id;
                UnitOfMeasure::create($data);
                session()->flash('success', __('Unit created successfully'));
            }

            $this->redirectRoute('app.inventory.units.index', navigate: true);
        } catch (\Exception $e) {
            $this->addError('name', __('Failed to save unit. Please try again.'));
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $baseUnits = UnitOfMeasure::baseUnits()
            ->active()
            ->when($this->unitId, fn ($q) => $q->where('id', '!=', $this->unitId))
            ->orderBy('name')
            ->get();

        $unitTypes = [
            'unit' => __('Unit'),
            'weight' => __('Weight'),
            'length' => __('Length'),
            'volume' => __('Volume'),
            'area' => __('Area'),
            'time' => __('Time'),
            'other' => __('Other'),
        ];

        return view('livewire.admin.units-of-measure.form', [
            'baseUnits' => $baseUnits,
            'unitTypes' => $unitTypes,
        ]);
    }
}
