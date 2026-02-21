<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\BranchScopedExists;
use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Use the branch from the route (branch-scoped API) or fall back to user's branch
        // The attributes->get('branch_id') path is used by SetBranchContext middleware
        $branchId = $this->route('branch')?->id
            ?? $this->attributes->get('branch_id')
            ?? $this->user()?->branch_id;

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', new BranchScopedExists('products', 'id', $branchId)],
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'lte:999999'],
            'items.*.price' => ['sometimes', 'numeric', 'gte:0'],
            'items.*.discount' => ['sometimes', 'numeric', 'gte:0'],
            'items.*.percent' => ['sometimes', 'boolean'],
            'items.*.tax_id' => ['sometimes', 'integer', new BranchScopedExists('taxes', 'id', $branchId, allowNull: true)],
            'customer_id' => ['sometimes', 'integer', new BranchScopedExists('customers', 'id', $branchId, allowNull: true)],
            // CRIT-POS-01 FIX: POS checkout always moves stock and POSService requires warehouse_id.
            // Make it explicit at validation time so the UI gets a clear 422 validation response
            // instead of a later abort() inside the service.
            'warehouse_id' => ['required', 'integer', new BranchScopedExists('warehouses', 'id', $branchId, allowNull: true)],
            // V59-CRIT-03 FIX: Validate payments to prevent negative amounts and unstructured data
            'payments' => ['sometimes', 'array'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.method' => ['required', 'string', 'in:cash,card,transfer,cheque'],
            // Optional payment metadata (flat)
            'payments.*.currency' => ['sometimes', 'string', 'max:3'],
            'payments.*.payment_date' => ['sometimes', 'date'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:191'],
            // Backward compatibility (legacy clients)
            'payments.*.reference_no' => ['nullable', 'string', 'max:191'],
            'payments.*.bank_name' => ['nullable', 'string', 'max:191'],
            'payments.*.card_last_four' => ['nullable', 'string', 'max:4'],
            'payments.*.cheque_number' => ['nullable', 'string', 'max:191'],
            'payments.*.cheque_date' => ['nullable', 'date'],
            'payments.*.notes' => ['nullable', 'string', 'max:1000'],

            // Backward-compatible nested meta object from older POS clients
            'payments.*.meta' => ['sometimes', 'array'],
            'payments.*.meta.reference_number' => ['nullable', 'string', 'max:191'],
            'payments.*.meta.reference_no' => ['nullable', 'string', 'max:191'],
            'payments.*.meta.bank_name' => ['nullable', 'string', 'max:191'],
            'payments.*.meta.card_last_four' => ['nullable', 'string', 'max:4'],
            'payments.*.meta.cheque_number' => ['nullable', 'string', 'max:191'],
            'payments.*.meta.cheque_date' => ['nullable', 'date'],
            'payments.*.meta.notes' => ['nullable', 'string', 'max:1000'],
            'payments.*.meta.card_type' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors()->toArray())
        );
    }
}
