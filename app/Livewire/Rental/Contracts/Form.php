<?php

declare(strict_types=1);

namespace App\Livewire\Rental\Contracts;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalPeriod;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Rules\BranchScopedExists;
use App\Services\Contracts\ModuleFieldServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use HasMultilingualValidation;
    use WithFileUploads;

    #[Locked]
    public ?int $contractId = null;

    /**
     * Uploaded contract files (documents, images).
     */
    public array $contractFiles = [];

    /**
     * Existing files (for edit mode).
     * Note: This is loaded from DB on mount and should not be trusted from client.
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

        // H1 FIX: Remove fallback to branch 1 - require explicit branch assignment
        $branchId = $user->branch_id;
        if (! $branchId && ! $user->can('rental.contracts.manage-all')) {
            // User has no branch and doesn't have manage-all permission
            abort(403, __('No branch assigned. Please contact administrator.'));
        }
        $branchId = (int) ($branchId ?? 0);
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
            $this->form['start_date'] = $model->start_date ? $model->start_date->format('Y-m-d') : null;
            $this->form['end_date'] = $model->end_date ? $model->end_date->format('Y-m-d') : null;
            $this->form['rent'] = decimal_float($model->rent_amount);
            $this->form['deposit'] = decimal_float($model->deposit_amount);
            $this->form['status'] = (string) $model->status;

            // Map rent_frequency back to a rental period ID for the form
            if ($model->rent_frequency) {
                $matchingPeriod = collect($this->availablePeriods)->firstWhere('type', $model->rent_frequency);
                $this->form['rental_period_id'] = $matchingPeriod ? $matchingPeriod['id'] : null;
            }

            // Check if custom days should be shown
            if ($this->form['rental_period_id']) {
                $period = collect($this->availablePeriods)->firstWhere('id', $this->form['rental_period_id']);
                $this->showCustomDays = $period && $period['type'] === 'custom';
            }

            $this->dynamicData = (array) ($model->documents ?? []);

            // Load existing files
            $this->existingFiles = $model->documents['attachments'] ?? [];
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
        $branchId = (int) $this->form['branch_id'];

        // H1 FIX: Use branch-scoped validation for tenant and unit
        return [
            'form.branch_id' => ['required', 'integer'],
            'form.unit_id' => [
                'required',
                'integer',
                // Validate unit belongs to a property in the same branch
                Rule::exists('rental_units', 'id')->whereIn(
                    'property_id',
                    Property::where('branch_id', $branchId)->select('id')
                ),
            ],
            'form.tenant_id' => ['required', 'integer', new BranchScopedExists('tenants', 'id', $branchId)],
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
        // H1 FIX: Reload attachments from DB instead of trusting client state
        if (! $this->contractId) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // H1 FIX: Use branch-scoped query to ensure user can only access contracts from their branch
        $query = RentalContract::query();
        if ($user->branch_id && ! $user->can('rental.contracts.manage-all')) {
            $query->where('branch_id', $user->branch_id);
        }
        $contract = $query->find($this->contractId);

        if (! $contract) {
            return;
        }

        // Get attachments from DB, not from client state
        $dbDocuments = $contract->documents ?? [];
        $dbAttachments = $dbDocuments['attachments'] ?? [];

        if (! isset($dbAttachments[$index])) {
            session()->flash('error', __('File not found'));

            return;
        }

        $file = $dbAttachments[$index];

        // Validate path doesn't contain traversal attempts or null bytes
        if (isset($file['path']) && ! str_contains($file['path'], '..') && ! str_contains($file['path'], "\0")) {
            // Delete from storage
            if (Storage::disk('private')->exists($file['path'])) {
                Storage::disk('private')->delete($file['path']);
            }
        }

        // Remove from array using DB data
        unset($dbAttachments[$index]);
        $dbAttachments = array_values($dbAttachments);

        // Update contract
        $documents = $contract->documents ?? [];
        $documents['attachments'] = $dbAttachments;
        $contract->documents = $documents;
        $contract->save();

        // Sync local state with DB
        $this->existingFiles = $dbAttachments;

        session()->flash('success', __('File removed successfully'));
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
        $contract->start_date = $this->form['start_date'] ?: null;
        $contract->end_date = $this->form['end_date'] ?: null;
        $contract->rent_amount = decimal_float($this->form['rent']);
        $contract->deposit_amount = decimal_float($this->form['deposit']);
        $contract->status = (string) $this->form['status'];

        // Map rental period selection to rent_frequency
        if ($this->form['rental_period_id']) {
            $period = RentalPeriod::find((int) $this->form['rental_period_id']);
            if ($period) {
                $contract->rent_frequency = $period->period_type ?? 'monthly';
            }
        }

        // Auto-generate contract number for new contracts
        if (! $contract->exists) {
            $branchId = (int) $this->form['branch_id'];
            $lastNumber = RentalContract::where('branch_id', $branchId)
                ->withTrashed()
                ->count() + 1;
            $contract->contract_number = 'RC-' . str_pad((string) $lastNumber, 6, '0', STR_PAD_LEFT);
            $contract->created_by = Auth::id();
        }

        // Store dynamic data in documents JSON column
        $contract->documents = $this->dynamicData;

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
            $existingDocs = $contract->documents ?? [];
            $existingAttachments = $existingDocs['attachments'] ?? [];
            $existingDocs['attachments'] = array_merge($existingAttachments, $uploadedFiles);
            $contract->documents = $existingDocs;
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
