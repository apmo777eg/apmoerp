<?php

declare(strict_types=1);

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;

    public ?Supplier $supplier = null;

    public bool $editMode = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $country = '';

    public string $tax_number = '';

    public string $company_name = '';

    public string $contact_person = '';

    public string $notes = '';

    public string $payment_terms = '';

    public float $minimum_order_value = 0;

    public string $supplier_rating = '';

    public float $quality_rating = 0;

    public float $delivery_rating = 0;

    public float $service_rating = 0;

    public bool $is_active = true;

    protected function rules(): array
    {
        $supplierId = $this->supplier?->id;
        $branchId = auth()->user()?->branches()->first()?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')
                    ->where('branch_id', $branchId)
                    ->ignore($supplierId),
            ],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers', 'tax_number')
                    ->where('branch_id', $branchId)
                    ->ignore($supplierId),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_terms' => ['nullable', 'in:immediate,net15,net30,net60,net90'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            'supplier_rating' => ['nullable', 'string', 'max:191'],
            'quality_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'delivery_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'service_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_active' => ['boolean'],
        ];
    }

    public function mount(?Supplier $supplier = null): void
    {
        $this->authorize('suppliers.manage');

        if ($supplier && $supplier->exists) {
            $this->supplier = $supplier;
            $this->editMode = true;

            // Explicitly set all fields to ensure proper initialization
            $this->name = $supplier->name ?? '';
            $this->email = $supplier->email ?? '';
            $this->phone = $supplier->phone ?? '';
            $this->address = $supplier->address ?? '';
            $this->city = $supplier->city ?? '';
            $this->country = $supplier->country ?? '';
            $this->tax_number = $supplier->tax_number ?? '';
            $this->company_name = $supplier->company_name ?? '';
            $this->contact_person = $supplier->contact_person ?? '';
            $this->payment_terms = $supplier->payment_terms ?? '';
            $this->minimum_order_value = decimal_float($supplier->minimum_order_value ?? 0);
            $this->supplier_rating = $supplier->supplier_rating ?? '';
            $this->quality_rating = decimal_float($supplier->quality_rating ?? 0);
            $this->delivery_rating = decimal_float($supplier->delivery_rating ?? 0);
            $this->service_rating = decimal_float($supplier->service_rating ?? 0);
            $this->notes = $supplier->notes ?? '';
            $this->is_active = (bool) ($supplier->is_active ?? true);
        }
    }

    public function save(): mixed
    {
        $validated = $this->validate();
        $validated['branch_id'] = auth()->user()->branches()->first()?->id;

        if ($this->editMode) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $validated['updated_by'] = actual_user_id();
        } else {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $validated['created_by'] = actual_user_id();
        }

        return $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->supplier->update($validated);
                } else {
                    Supplier::create($validated);
                }
            },
            successMessage: $this->editMode ? __('Supplier updated successfully') : __('Supplier created successfully'),
            redirectRoute: 'suppliers.index'
        );
    }

    public function render()
    {
        return view('livewire.suppliers.form')
            ->layout('layouts.app', ['title' => $this->editMode ? __('Edit Supplier') : __('Add Supplier')]);
    }
}
