<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\BranchContextManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * BranchScopedExists - Validates that a record exists within the current branch context.
 *
 * V57-CRITICAL-03 FIX: This rule ensures that `exists:` validations on branch-owned tables
 * are constrained by the current branch, preventing cross-branch data references.
 *
 * Usage:
 *   'product_id' => ['required', new BranchScopedExists('products')],
 *   'warehouse_id' => ['required', new BranchScopedExists('warehouses', 'id', $customBranchId)],
 *
 * @see https://laravel.com/docs/validation#custom-validation-rules
 */
class BranchScopedExists implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param string $table The database table to check
     * @param string $column The column to match against (default: 'id')
     * @param int|null $branchId Optional branch ID (uses current context if null)
     * @param bool $allowNull Whether to allow null/empty values (default: false)
     */
    public function __construct(
        protected string $table,
        protected string $column = 'id',
        protected ?int $branchId = null,
        protected bool $allowNull = false
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow null values if configured
        if ($this->allowNull && ($value === null || $value === '')) {
            return;
        }

        // Get branch ID from parameter or current context
        $branchId = $this->branchId ?? BranchContextManager::getCurrentBranchId();

        // If no branch context is available, fail closed for security
        if ($branchId === null) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
            return;
        }

        // Check if record exists in the table with matching branch_id
        $exists = DB::table($this->table)
            ->where($this->column, $value)
            ->where('branch_id', $branchId)
            ->exists();

        if (! $exists) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
        }
    }
}
