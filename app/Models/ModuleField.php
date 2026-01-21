<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ModuleField Model
 * 
 * V57-CRITICAL-02 FIX: Added HasBranch trait for proper branch scoping.
 * Module fields can be branch-specific and should be isolated by branch.
 */
class ModuleField extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'module_fields';

    protected $fillable = [
        'branch_id',
        'module_key',
        'entity',
        'name',
        'label',
        'type',
        'options',
        'rules',
        'is_required',
        'is_visible',
        'show_in_list',
        'show_in_export',
        'order',
        'default',
        'meta',
        'field_category',
        'validation_rules',
        'computed_config',
        'is_system',
        'is_searchable',
        'supports_bulk_edit',
        'dependencies',
    ];

    protected $casts = [
        'options' => 'array',
        'rules' => 'array',
        'is_required' => 'bool',
        'is_visible' => 'bool',
        'show_in_list' => 'bool',
        'show_in_export' => 'bool',
        'order' => 'int',
        'default' => 'array',
        'meta' => 'array',
        'branch_id' => 'int',
        'validation_rules' => 'array',
        'computed_config' => 'array',
        'is_system' => 'bool',
        'is_searchable' => 'bool',
        'supports_bulk_edit' => 'bool',
        'dependencies' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForModule(Builder $query, string $moduleKey): Builder
    {
        return $query->where('module_key', $moduleKey);
    }

    public function scopeForEntity(Builder $query, string $entity): Builder
    {
        return $query->where('entity', $entity);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('order');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('field_category', $category);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true);
    }

    public function scopeBulkEditable(Builder $query): Builder
    {
        return $query->where('supports_bulk_edit', true);
    }

    /**
     * Check if field has dependencies
     */
    public function hasDependencies(): bool
    {
        return ! empty($this->dependencies);
    }

    /**
     * Check if dependencies are satisfied
     */
    public function dependenciesSatisfied(array $context): bool
    {
        if (! $this->hasDependencies()) {
            return true;
        }

        foreach ($this->dependencies as $field => $expectedValue) {
            if (! isset($context[$field]) || $context[$field] !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get computed value if field is computed
     */
    public function getComputedValue(array $data)
    {
        if (empty($this->computed_config)) {
            return null;
        }

        // Simple computed field logic - can be extended
        $formula = $this->computed_config['formula'] ?? null;
        if (! $formula) {
            return null;
        }

        // Basic formula evaluation (can be enhanced with a formula parser)
        return $this->evaluateFormula($formula, $data);
    }

    /**
     * Evaluate formula (basic implementation)
     */
    protected function evaluateFormula(string $formula, array $data)
    {
        // This is a placeholder for formula evaluation
        // In production, use a proper expression evaluator
        return null;
    }
}
