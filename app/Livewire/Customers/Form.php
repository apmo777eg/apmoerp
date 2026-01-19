<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;
    use HasMultilingualValidation;

    public ?Customer $customer = null;

    public bool $editMode = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $tax_number = '';

    public float $credit_limit = 0;

    public string $notes = '';

    public float $discount_percentage = 0;

    public string $payment_terms = '';

    public int $payment_due_days = 0;

    public string $preferred_currency = '';

    public string $billing_address = '';

    public string $shipping_address = '';

    public string $status = 'active';

    private static array $customerColumns = [];

    public function getRules(): array
    {
        return [
            'name' => $this->multilingualString(required: true, max: 255),
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_terms' => 'nullable|in:immediate,net15,net30,net60,net90',
            'payment_due_days' => 'nullable|integer|min:0',
            'preferred_currency' => 'nullable|string|size:3',
            'billing_address' => $this->unicodeText(required: false, max: 500),
            'shipping_address' => $this->unicodeText(required: false, max: 500),
            'status' => 'required|in:active,inactive',
            'notes' => $this->unicodeText(required: false),
        ];
    }

    public function mount(?Customer $customer = null): void
    {
        $user = auth()->user();

        if ($customer && $customer->exists) {
            $this->authorize('customers.manage');
            if ($user?->branch_id && $customer->branch_id !== $user->branch_id && ! $this->isSuperAdmin($user)) {
                abort(403);
            }
            $this->customer = $customer;
            $this->editMode = true;

            // Explicitly set all fields to ensure proper initialization
            $this->name = $customer->name ?? '';
            $this->email = $customer->email ?? '';
            $this->phone = $customer->phone ?? '';
            $this->tax_number = $customer->tax_number ?? '';
            $this->credit_limit = decimal_float($customer->credit_limit ?? 0);
            $this->discount_percentage = decimal_float($customer->discount_percentage ?? 0);
            $this->payment_terms = $customer->payment_terms ?? '';
            $this->payment_due_days = (int) ($customer->payment_due_days ?? 0);
            $this->preferred_currency = $customer->preferred_currency ?? '';
            $this->billing_address = $customer->billing_address ?? '';
            $this->shipping_address = $customer->shipping_address ?? '';
            $this->status = $customer->status ?? 'active';
            $this->notes = $customer->notes ?? '';
        } else {
            $this->authorize('customers.manage');
        }
    }

    public function save(): mixed
    {
        $validated = $this->validate($this->getRules());

        // Get the user's branch - handle both direct branch_id and relationship
        $user = auth()->user();
        $branchId = $this->customer?->branch_id ?? $user?->branch_id ?? $user?->branches()->first()?->id;

        if (! $branchId && ! $this->isSuperAdmin($user)) {
            abort(403);
        }

        $validated['branch_id'] = $branchId;

        // Ensure status aligns with the database column
        $validated['status'] = $validated['status'] ?? 'active';

        // Only set created_by for new records
        if (! $this->editMode) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $validated['created_by'] = actual_user_id();
        }

        if (empty(self::$customerColumns)) {
            self::$customerColumns = Schema::getColumnListing('customers');
        }

        $validated = array_intersect_key($validated, array_flip(self::$customerColumns));

        return $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->customer->update($validated);
                } else {
                    Customer::create($validated);
                }
            },
            successMessage: $this->editMode ? __('Customer updated successfully') : __('Customer created successfully'),
            redirectRoute: 'customers.index'
        );
    }

    public function render()
    {
        return view('livewire.customers.form')
            ->layout('layouts.app', ['title' => $this->editMode ? __('Edit Customer') : __('Add Customer')]);
    }

    private function isSuperAdmin(?User $user): bool
    {
        return (bool) $user?->hasAnyRole(['super-admin', 'Super Admin']);
    }
}
