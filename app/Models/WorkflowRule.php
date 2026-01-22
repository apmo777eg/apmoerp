<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRule extends BaseModel
{
    protected $fillable = [
        'workflow_definition_id',
        'branch_id',
        'name',
        'priority',
        'conditions',
        'actions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if rule conditions match the given data
     */
    public function matches(array $data): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (! $this->evaluateCondition($condition, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (! $field || ! isset($data[$field])) {
            return false;
        }

        $dataValue = $data[$field];

        return match ($operator) {
            '=', '==' => $dataValue == $value,
            '!=' => $dataValue != $value,
            '>' => $dataValue > $value,
            '<' => $dataValue < $value,
            '>=' => $dataValue >= $value,
            '<=' => $dataValue <= $value,
            'in' => in_array($dataValue, (array) $value),
            'not_in' => ! in_array($dataValue, (array) $value),
            'contains' => str_contains((string) $dataValue, (string) $value),
            'starts_with' => str_starts_with((string) $dataValue, (string) $value),
            'ends_with' => str_ends_with((string) $dataValue, (string) $value),
            default => false,
        };
    }
}
