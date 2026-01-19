<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Units;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\Property;
use App\Models\RentalUnit;
use App\Services\Contracts\ModuleFieldServiceInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use HasMultilingualValidation;

    public ?int $unitId = null;

    /**
     * Base rental unit fields.
     *
     * @var array{property_id:int,code:string,type:?string,status:string,rent:float,deposit:float}
     */
    public array $form = [
        'property_id' => 0,
        'code' => '',
        'type' => '',
        'status' => 'available',
        'rent' => 0.0,
        'deposit' => 0.0,
    ];

    /**
     * Dynamic field schema for rental units.
     *
     * @var array<int,array<string,mixed>>
     */
    public array $dynamicSchema = [];

    /**
     * Dynamic field values mapped by field key.
     *
     * @var array<string,mixed>
     */
    public array $dynamicData = [];

    /**
     * Available properties for the current branch.
     *
     * @var array<int,array{id:int,label:string}>
     */
    public array $availableProperties = [];

    public function mount(ModuleFieldServiceInterface $moduleFields, ?int $unit = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('rental.units.view')) {
            abort(403);
        }

        $this->unitId = $unit;

        $branchId = (int) ($user->branch_id ?? 1);

        // Load properties for this branch
        $this->availableProperties = Property::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Property $p): array {
                return [
                    'id' => $p->id,
                    'label' => $p->name,
                ];
            })
            ->all();

        if (! $this->form['property_id'] && ! empty($this->availableProperties)) {
            $this->form['property_id'] = (int) $this->availableProperties[0]['id'];
        }

        // Load dynamic schema for rentals.units
        $this->dynamicSchema = $moduleFields->formSchema('rentals', 'units', $branchId);

        if ($this->unitId) {
            /** @var RentalUnit $unitModel */
            $unitModel = RentalUnit::query()->with('property')->findOrFail($this->unitId);

            $this->form['property_id'] = (int) $unitModel->property_id;
            $this->form['code'] = (string) $unitModel->code;
            $this->form['type'] = $unitModel->type ?? '';
            $this->form['status'] = (string) $unitModel->status;
            $this->form['rent'] = decimal_float($unitModel->rent);
            $this->form['deposit'] = decimal_float($unitModel->deposit);

            $this->dynamicData = (array) ($unitModel->extra_attributes ?? []);
        } else {
            // Defaults for dynamic fields from schema (if any)
            foreach ($this->dynamicSchema as $field) {
                $name = $field['name'] ?? null;
                if (! $name) {
                    continue;
                }

                if (array_key_exists('default', $field)) {
                    $this->dynamicData[$name] = $field['default'];
                } else {
                    $this->dynamicData[$name] = null;
                }
            }
        }
    }

    protected function rules(): array
    {
        return [
            'form.property_id' => ['required', 'integer', 'exists:properties,id'],
            'form.code' => ['required', 'string', 'max:100'],
            'form.type' => ['nullable', 'string', 'max:100'],
            'form.status' => ['required', 'string', 'max:50'],
            'form.rent' => ['required', 'numeric', 'min:0'],
            'form.deposit' => ['required', 'numeric', 'min:0'],
        ];
    }

    #[On('dynamic-form-updated')]
    public function handleDynamicFormUpdated(array $data): void
    {
        $this->dynamicData = $data;
    }

    public function save(): mixed
    {
        $user = Auth::user();
        if (! $user || ! $user->can('rental.units.manage')) {
            abort(403);
        }

        $this->validate();

        if ($this->unitId) {
            /** @var RentalUnit $unit */
            $unit = RentalUnit::query()->findOrFail($this->unitId);
        } else {
            $unit = new RentalUnit;
        }

        $unit->property_id = (int) $this->form['property_id'];
        $unit->code = (string) $this->form['code'];
        $unit->type = $this->form['type'] !== '' ? (string) $this->form['type'] : null;
        $unit->status = (string) $this->form['status'];
        $unit->rent = decimal_float($this->form['rent']);
        $unit->deposit = decimal_float($this->form['deposit']);
        $unit->extra_attributes = $this->dynamicData;

        $unit->save();

        $this->unitId = $unit->id;

        session()->flash(
            'status',
            $this->unitId
                ? __('Rental unit updated successfully.')
                : __('Rental unit created successfully.')
        );

        $this->redirectRoute('app.rental.units.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.rental.units.form');
    }
}
