<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasPaymentTermsValidation;
use Illuminate\Foundation\Http\FormRequest;

class SupplierStoreRequest extends FormRequest
{
    use HasPaymentTermsValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190', 'unique:suppliers,email'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],            // Financial fields
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            // Backward-compat: accept legacy key
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            // Rating fields (0-5)
            'rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            // Backward-compat: accept legacy key
            'supplier_rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'quality_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'delivery_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'service_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ],
            $this->paymentTermsRules(),
            $this->paymentDueDaysRules()
        );
    }
}
