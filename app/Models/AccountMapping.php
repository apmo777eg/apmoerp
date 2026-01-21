<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountMapping - Account mapping configuration model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Account mappings must be isolated by branch to prevent cross-branch
 * configuration leakage in accounting setup.
 */
class AccountMapping extends Model
{
    use HasBranch;
    protected $fillable = [
        'branch_id',
        'module_name',
        'mapping_key',
        'account_id',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get account mapping for a specific module and key
     */
    public static function getAccount(string $moduleName, string $mappingKey, ?int $branchId = null): ?Account
    {
        $query = static::where('module_name', $moduleName)
            ->where('mapping_key', $mappingKey)
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $mapping = $query->first();

        return $mapping?->account;
    }

    /**
     * Check if conditions match
     */
    public function matchesConditions(array $data): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (! $field || ! isset($data[$field])) {
                continue;
            }

            $dataValue = $data[$field];

            switch ($operator) {
                case '=':
                case '==':
                    if ($dataValue != $value) {
                        return false;
                    }
                    break;
                case '>':
                    if ($dataValue <= $value) {
                        return false;
                    }
                    break;
                case '<':
                    if ($dataValue >= $value) {
                        return false;
                    }
                    break;
                case '>=':
                    if ($dataValue < $value) {
                        return false;
                    }
                    break;
                case '<=':
                    if ($dataValue > $value) {
                        return false;
                    }
                    break;
                case 'in':
                    if (! in_array($dataValue, (array) $value)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}
