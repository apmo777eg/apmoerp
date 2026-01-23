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

    public ?int $payment_terms_days = null;

    public float $minimum_order_amount = 0;

    public ?int $rating = null;

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
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'integer', 'min:0', 'max:5'],
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
            $this->payment_terms_days = $supplier->payment_terms_days;
            $this->minimum_order_amount = decimal_float($supplier->minimum_order_amount ?? 0);
            $this->rating = $supplier->rating;
            $this->notes = $supplier->notes ?? '';
            $this->is_active = (bool) ($supplier->is_active ?? true);
        }
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize('suppliers.manage');

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
