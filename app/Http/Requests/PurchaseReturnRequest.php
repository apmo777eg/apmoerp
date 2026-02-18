<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.return') ?? false;
    }

    public function rules(): array
    {
        // V25-HIGH-09 FIX: Add validation rules for purchase return payload
        $purchaseId = $this->route('purchase');
        $branchId = $this->attributes->get('branch_id') ?? $this->user()?->branch_id;

        return [
            'reason' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->when($branchId, function ($rule) use ($branchId) {
                    return $rule->where('branch_id', $branchId);
                }),
            ],
            'items.*.purchase_item_id' => [
                'required',
                'integer',
                Rule::exists('purchase_items', 'id')->where('purchase_id', $purchaseId),
            ],
            'items.*.qty_returned' => ['required', 'numeric', 'min:0.001'],
            'items.*.item_condition' => ['nullable', 'in:defective,damaged,wrong_item,excess,expired'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
