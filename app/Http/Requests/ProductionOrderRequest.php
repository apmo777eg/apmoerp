<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Rules\BranchScopedExists;
use Illuminate\Foundation\Http\FormRequest;

class ProductionOrderRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()->can('manufacturing.create') || $this->user()->can('manufacturing.update');
    }

    public function rules(): array
    {
        $branchId = $this->user()?->branch_id;

        return [
            'bom_id' => ['required', new BranchScopedExists('bills_of_materials', 'id', $branchId)],
            'reference_number' => ['sometimes', 'string', 'max:50', 'unique:production_orders,reference_number,'.($this->route('order') ? $this->route('order')->id : 'NULL')],

            'planned_quantity' => ['required', 'numeric', 'min:0.01'],
            'planned_start_date' => ['required', 'date'],
            'planned_end_date' => ['required', 'date', 'after_or_equal:planned_start_date'],

            'priority' => ['required', 'in:low,normal,high,urgent'],
            'status' => ['sometimes', 'in:draft,pending,in_progress,completed,cancelled'],

            'notes' => $this->unicodeText(required: false),
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('branch_id') && $this->user()->branch_id) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }

        if ($this->isMethod('POST') && ! $this->has('status')) {
            $this->merge([
                'status' => 'draft',
            ]);
        }
    }
}
