<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseStoreRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('purchases.create') ?? false;
    }

    public function rules(): array
    {
        // V25-HIGH-08 FIX: Get branch_id from request context or authenticated user
        $branchId = $this->attributes->get('branch_id') ?? $this->user()?->branch_id;

        return [
            // V25-HIGH-08 FIX: Scope supplier validation to branch
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->when($branchId, function ($rule) use ($branchId) {
                    return $rule->where('branch_id', $branchId);
                }),
            ],
            // V25-HIGH-08 FIX: Scope warehouse validation to branch
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->when($branchId, function ($rule) use ($branchId) {
                    return $rule->where('branch_id', $branchId);
                }),
            ],
            'items' => ['required', 'array', 'min:1'],
            // V25-HIGH-08 FIX: Scope product validation to branch
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->when($branchId, function ($rule) use ($branchId) {
                    return $rule->where('branch_id', $branchId);
                }),
            ],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'gte:0'],
            // New tracking fields
            'expected_date' => ['nullable', 'date'],
            'shipping_method' => $this->multilingualString(required: false, max: 191),
            'supplier_notes' => $this->unicodeText(required: false, max: 1000),
            'internal_notes' => $this->unicodeText(required: false, max: 1000),
            // Payment fields
            'payment_status' => ['nullable', 'in:unpaid,partial,paid'],
            'due_date' => ['nullable', 'date'],
'discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
