<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Contracts;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\RentalContract;
use App\Models\RentalPeriod;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Services\Contracts\ModuleFieldServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use HasMultilingualValidation;
    use WithFileUploads;

    public ?int $contractId = null;

    /**
     * Uploaded contract files (documents, images).
     */
    public array $contractFiles = [];

    /**
     * Existing files (for edit mode).
     */
    public array $existingFiles = [];

    /**
     * @var array{branch_id:int,unit_id:int,tenant_id:int,rental_period_id:?int,custom_days:?int,start_date:?string,end_date:?string,rent:float,deposit:float,status:string}
     */
    public array $form = [
        'branch_id' => 0,
        'unit_id' => 0,
        'tenant_id' => 0,
        'rental_period_id' => null,
        'custom_days' => null,
        'start_date' => null,
        'end_date' => null,
        'rent' => 0.0,
        'deposit' => 0.0,
        'status' => 'draft',
    ];

    /**
     * Available rental periods.
     *
     * @var array<int,array{id:int,label:string,type:string}>
     */
    public array $availablePeriods = [];

    /**
     * Whether custom days input should be shown.
     */
    public bool $showCustomDays = false;

    /**
     * Dynamic field schema for rental contracts.
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
     * Available units for the current branch.
     *
     * @var array<int,array{id:int,label:string}>
     */
    public array $availableUnits = [];

    /**
     * Available tenants for the current branch.
     *
     * @var array<int,array{id:int,label:string}>
     */
    public array $availableTenants = [];

    public function mount(ModuleFieldServiceInterface $moduleFields, ?int $contract = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('rental.contracts.view')) {
            abort(403);
        }

        $this->contractId = $contract;

        $branchId = (int) ($user->branch_id ?? 1);
        $this->form['branch_id'] = $branchId;

        // Load units for this branch with eager loading
        $this->availableUnits = RentalUnit::query()
            ->with('property')
            ->whereHas('property', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('code')
            ->get()
            ->map(function (RentalUnit $u): array {
                return [
                    'id' => $u->id,
                    'label' => $u->code.($u->property ? ' - '.$u->property->name : ''),
                ];
            })
            ->all();

        // Load tenants for this branch
        $this->availableTenants = Tenant::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get()
            ->map(function (Tenant $t): array {
                return [
                    'id' => $t->id,
                    'label' => $t->name,
                ];
            })
            ->all();

        // Load rental periods for the rental module
        $this->availablePeriods = RentalPeriod::query()
            ->where('module_id', 5) // Rental module
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (RentalPeriod $p): array {
                return [
                    'id' => $p->id,
                    'label' => $p->localizedName,
                    'type' => $p->period_type,
                ];
            })
            ->all();

        if (! $this->form['unit_id'] && ! empty($this->availableUnits)) {
            $this->form['unit_id'] = (int) $this->availableUnits[0]['id'];
        }

        if (! $this->form['tenant_id'] && ! empty($this->availableTenants)) {
            $this->form['tenant_id'] = (int) $this->availableTenants[0]['id'];
        }

        // Set default rental period (monthly)
        $defaultPeriod = collect($this->availablePeriods)->firstWhere('type', 'monthly');
        if ($defaultPeriod && ! $this->form['rental_period_id']) {
            $this->form['rental_period_id'] = $defaultPeriod['id'];
        }

        // Load dynamic schema for rentals.contracts
        $this->dynamicSchema = $moduleFields->formSchema('rentals', 'contracts', $branchId);

        if ($this->contractId) {
            /** @var RentalContract $model */
            $model = RentalContract::query()->with(['tenant', 'unit'])->findOrFail($this->contractId);

            $this->form['branch_id'] = (int) $model->branch_id;
            $this->form['unit_id'] = (int) $model->unit_id;
            $this->form['tenant_id'] = (int) $model->tenant_id;
            $this->form['rental_period_id'] = $model->rental_period_id ? (int) $model->rental_period_id : null;
            $this->form['custom_days'] = $model->custom_days ? (int) $model->custom_days : null;
            $this->form['start_date'] = $model->start_date ? $model->start_date->format('Y-m-d') : null;
            $this->form['end_date'] = $model->end_date ? $model->end_date->format('Y-m-d') : null;
            $this->form['rent'] = decimal_float($model->rent);
            $this->form['deposit'] = decimal_float($model->deposit);
            $this->form['status'] = (string) $model->status;

            // Check if custom days should be shown
            if ($model->rental_period_id) {
                $period = collect($this->availablePeriods)->firstWhere('id', $model->rental_period_id);
                $this->showCustomDays = $period && $period['type'] === 'custom';
            }

            $this->dynamicData = (array) ($model->extra_attributes ?? []);

            // Load existing files
            $this->existingFiles = $model->extra_attributes['attachments'] ?? [];
        } else {
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
            'form.branch_id' => ['required', 'integer'],
            'form.unit_id' => ['required', 'integer', 'exists:rental_units,id'],
            'form.tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'form.rental_period_id' => ['required', 'integer', 'exists:rental_periods,id'],
            'form.custom_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'form.start_date' => ['required', 'date'],
            'form.end_date' => ['nullable', 'date', 'after_or_equal:form.start_date'],
            'form.rent' => ['required', 'numeric', 'min:0'],
            'form.deposit' => ['required', 'numeric', 'min:0'],
            'form.status' => ['required', 'string', 'max:50'],
        ];
    }

    public function updatedFormRentalPeriodId($value): void
    {
        if ($value) {
            $period = collect($this->availablePeriods)->firstWhere('id', (int) $value);
            $this->showCustomDays = $period && $period['type'] === 'custom';

            // Calculate end date based on period and start date
            if ($this->form['start_date'] && ! $this->showCustomDays) {
                $this->calculateEndDate();
            }
        } else {
            $this->showCustomDays = false;
        }
    }

    public function updatedFormStartDate($value): void
    {
        if ($value && $this->form['rental_period_id']) {
            $this->calculateEndDate();
        }
    }

    public function updatedFormCustomDays($value): void
    {
        if ($value && $this->form['start_date']) {
            $this->calculateEndDate();
        }
    }

    protected function calculateEndDate(): void
    {
        if (! $this->form['start_date'] || ! $this->form['rental_period_id']) {
            return;
        }

        $period = RentalPeriod::find($this->form['rental_period_id']);
        if (! $period) {
            return;
        }

        $startDate = \Carbon\Carbon::parse($this->form['start_date']);

        if ($period->period_type === 'custom' && $this->form['custom_days']) {
            $this->form['end_date'] = $startDate->addDays((int) $this->form['custom_days'])->format('Y-m-d');
        } else {
            $this->form['end_date'] = match ($period->duration_unit) {
                'days' => $startDate->addDays($period->duration_value)->format('Y-m-d'),
                'weeks' => $startDate->addWeeks($period->duration_value)->format('Y-m-d'),
                'months' => $startDate->addMonths($period->duration_value)->format('Y-m-d'),
                'years' => $startDate->addYears($period->duration_value)->format('Y-m-d'),
                default => $startDate->addDays($period->duration_value)->format('Y-m-d'),
            };
        }
    }

    #[On('dynamic-form-updated')]
    public function handleDynamicFormUpdated(array $data): void
    {
        $this->dynamicData = $data;
    }

    public function removeExistingFile(int $index): void
    {
        if (isset($this->existingFiles[$index])) {
            $file = $this->existingFiles[$index];

            // Delete from storage
            if (isset($file['path']) && Storage::disk('private')->exists($file['path'])) {
                Storage::disk('private')->delete($file['path']);
            }

            // Remove from array
            unset($this->existingFiles[$index]);
            $this->existingFiles = array_values($this->existingFiles);

            // Update contract if it exists
            if ($this->contractId) {
                $contract = RentalContract::find($this->contractId);
                if ($contract) {
                    $attributes = $contract->extra_attributes ?? [];
                    $attributes['attachments'] = $this->existingFiles;
                    $contract->extra_attributes = $attributes;
                    $contract->save();
                }
            }

            session()->flash('success', __('File removed successfully'));
        }
    }

    public function save(): mixed
    {
        $user = Auth::user();
        if (! $user || ! $user->can('rental.contracts.manage')) {
            abort(403);
        }

        $this->validate();

        // Recalculate end_date server-side for security
        $this->calculateEndDate();

        if ($this->contractId) {
            /** @var RentalContract $contract */
            $contract = RentalContract::query()->findOrFail($this->contractId);
        } else {
            $contract = new RentalContract;
        }

        $contract->branch_id = (int) $this->form['branch_id'];
        $contract->unit_id = (int) $this->form['unit_id'];
        $contract->tenant_id = (int) $this->form['tenant_id'];
        $contract->rental_period_id = $this->form['rental_period_id'] ? (int) $this->form['rental_period_id'] : null;
        $contract->custom_days = $this->form['custom_days'] ? (int) $this->form['custom_days'] : null;
        $contract->start_date = $this->form['start_date'] ?: null;
        $contract->end_date = $this->form['end_date'] ?: null;
        $contract->rent = decimal_float($this->form['rent']);
        $contract->deposit = decimal_float($this->form['deposit']);
        $contract->status = (string) $this->form['status'];
        $contract->extra_attributes = $this->dynamicData;

        $contract->save();

        // Handle file uploads
        if (! empty($this->contractFiles)) {
            $uploadedFiles = [];

            foreach ($this->contractFiles as $file) {
                // Store file in rental-contracts directory
                $path = $file->store('rental-contracts/'.$contract->id, 'private');

                $uploadedFiles[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toIso8601String(),
                ];
            }

            // Merge with existing files
            $existingAttachments = $contract->extra_attributes['attachments'] ?? [];
            $contract->extra_attributes = array_merge(
                $contract->extra_attributes ?? [],
                ['attachments' => array_merge($existingAttachments, $uploadedFiles)]
            );
            $contract->save();

            // Clear uploaded files
            $this->contractFiles = [];
        }

        $this->contractId = $contract->id;

        session()->flash(
            'status',
            $this->contractId
                ? __('Rental contract updated successfully.')
                : __('Rental contract created successfully.')
        );

        $this->redirectRoute('app.rental.contracts.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.rental.contracts.form');
    }
}
