<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'supplier_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'internal_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'expected_date' => ['sometimes', 'nullable', 'date'],
            'shipping_method' => ['sometimes', 'nullable', 'string', 'max:191'],
            'payment_status' => ['sometimes', 'nullable', 'in:unpaid,partial,paid'],
            'due_date' => ['sometimes', 'nullable', 'date'],
'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
