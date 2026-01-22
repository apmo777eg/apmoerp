<?php

declare(strict_types=1);

namespace App\Livewire\FixedAssets;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\FixedAsset;
use App\Models\Supplier;
use App\Models\User;
use App\Rules\BranchScopedExists;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;
    use HasMultilingualValidation;

    public ?FixedAsset $asset = null;

    public bool $isEditing = false;

    // Form fields
    public string $name = '';

    public string $description = '';

    public string $category = '';

    public string $location = '';

    public string $purchase_date = '';

    public string $purchase_cost = '';

    public string $salvage_value = '0';

    public string $useful_life_years = '';

    public string $useful_life_months = '0';

    public string $depreciation_method = 'straight_line';

    public string $depreciation_rate = '';

    public ?int $supplier_id = null;

    public string $serial_number = '';

    public string $model = '';

    public string $manufacturer = '';

    public string $warranty_expiry = '';

    public ?int $assigned_to = null;

    public string $notes = '';

    protected function rules(): array
    {
        return array_merge([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1',
            'useful_life_months' => 'nullable|integer|min:0|max:11',
            'depreciation_method' => 'required|in:straight_line,declining_balance,units_of_production',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch supplier references
            'supplier_id' => ['nullable', new BranchScopedExists('suppliers', 'id', null, true)],
            'warranty_expiry' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ], [
            // Use multilingual validation for text fields
            'description' => $this->unicodeText(required: false),
            'serial_number' => $this->flexibleCode(required: false, max: 255),
            'model' => $this->multilingualString(required: false, max: 255),
            'manufacturer' => $this->multilingualString(required: false, max: 255),
            'notes' => $this->unicodeText(required: false),
        ]);
    }

    public function mount(?FixedAsset $asset = null): void
    {
        if ($asset && $asset->exists) {
            $this->authorize('fixed-assets.edit');
            $this->isEditing = true;
            $this->asset = $asset;
            $this->fill($asset->toArray());
            $this->purchase_date = $asset->purchase_date->format('Y-m-d');
            $this->warranty_expiry = $asset->warranty_expiry?->format('Y-m-d') ?? '';
        } else {
            $this->authorize('fixed-assets.create');
            $this->purchase_date = now()->format('Y-m-d');
        }
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize($this->asset ? 'fixed-assets.edit' : 'fixed-assets.create');

        $this->validate();

        $data = [
            'branch_id' => auth()->user()->branch_id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'location' => $this->location,
            'purchase_date' => $this->purchase_date,
            'purchase_cost' => $this->purchase_cost,
            'salvage_value' => $this->salvage_value,
            'useful_life_years' => $this->useful_life_years,
            'useful_life_months' => $this->useful_life_months ?: 0,
            'depreciation_method' => $this->depreciation_method,
            'depreciation_rate' => $this->depreciation_rate ?: null,
            'supplier_id' => $this->supplier_id,
            'serial_number' => $this->serial_number,
            'model' => $this->model,
            'manufacturer' => $this->manufacturer,
            'warranty_expiry' => $this->warranty_expiry ?: null,
            'assigned_to' => $this->assigned_to,
            'notes' => $this->notes,
            'status' => 'active',
        ];

        return $this->handleOperation(
            operation: function () use ($data) {
                if ($this->isEditing) {
                    // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                    $data['updated_by'] = actual_user_id();
                    $this->asset->update($data);
                } else {
                    // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                    $data['created_by'] = actual_user_id();
                    FixedAsset::create($data);
                }
            },
            successMessage: $this->isEditing
                ? __('Fixed asset updated successfully')
                : __('Fixed asset created successfully'),
            redirectRoute: 'app.fixed-assets.index'
        );
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $suppliers = Supplier::where('branch_id', auth()->user()->branch_id)
            ->orderBy('name')
            ->get();

        $users = User::whereHas('branches', function ($q) {
            $q->where('branches.id', auth()->user()->branch_id);
        })->orderBy('name')->get();

        return view('livewire.fixed-assets.form', [
            'suppliers' => $suppliers,
            'users' => $users,
        ]);
    }
}
