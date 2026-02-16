<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Services\BranchContextManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * HasBranch - Unified trait for branch-aware models
 *
 * Provides:
 *   - Auto-assignment of branch_id on create
 *   - Global scope for multi-tenancy (filters by user's branch)
 *   - Branch relationship
 *   - Scopes: forBranch, forCurrentBranch, forUserBranches, inRequestBranch
 *   - Helper methods: belongsToBranch, isAccessibleByUser
 */
trait HasBranch
{
    /**
     * Cache table -> has branch_id column.
     *
     * Important for performance (avoids repeated Schema::hasColumn calls),
     * and for safety when migrations are out of sync.
     *
     * @var array<string, bool>
     */
    protected static array $branchIdColumnCache = [];

    public static function bootHasBranch(): void
    {
        // Apply global branch scope for multi-tenancy isolation
        static::addGlobalScope(new BranchScope);

        static::creating(function (Model $model): void {
            // Only auto-assign branch_id if:
            // 1) branch_id is not explicitly set
            // 2) the underlying table actually has a branch_id column
            //
            // V57-HIGH-01 FIX: Use schema inspection (cached) instead of relying on $fillable.
            // $fillable can omit branch_id for valid mass-assignment reasons, which previously
            // caused records to be created with NULL branch_id and then disappear due to BranchScope.
            if (! $model->getAttribute('branch_id') && static::tableHasBranchIdColumn($model)) {
                // First try the model's own currentBranchId method
                if (method_exists($model, 'currentBranchId')) {
                    $branchId = $model->currentBranchId();
                    if ($branchId) {
                        $model->setAttribute('branch_id', $branchId);

                        return;
                    }
                }

                // Fallback to BranchContextManager for the current branch
                $branchId = BranchContextManager::getCurrentBranchId();
                if ($branchId) {
                    $model->setAttribute('branch_id', $branchId);
                }
            }
        });
    }

    /**
     * Determine if a model's table has a branch_id column.
     *
     * We cache the result by table name to avoid repeated schema queries.
     * If schema inspection fails (e.g., during early bootstrap/migrations),
     * we fall back to the legacy $fillable heuristic.
     */
    protected static function tableHasBranchIdColumn(Model $model): bool
    {
        $table = $model->getTable();

        if (array_key_exists($table, static::$branchIdColumnCache)) {
            return static::$branchIdColumnCache[$table];
        }

        try {
            return static::$branchIdColumnCache[$table] = Schema::hasColumn($table, 'branch_id');
        } catch (\Throwable) {
            // Best-effort fallback – avoids crashing bootstrapping tasks.
            return static::$branchIdColumnCache[$table] = in_array('branch_id', $model->getFillable(), true);
        }
    }

    /**
     * Disable the branch scope for a query.
     * Useful for admin interfaces that need to see all branches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope by specific branch ID
     */
    public function scopeForBranch(Builder $query, $branch): Builder
    {
        $id = is_object($branch) ? $branch->getKey() : $branch;

        return $query->where($this->getTable().'.branch_id', $id);
    }

    /**
     * Scope by current user's branch
     */
    public function scopeForCurrentBranch(Builder $query, ?object $user = null): Builder
    {
        $user = $user ?? $this->resolveCurrentUser();
        $branchId = $user?->branch_id;

        if ($branchId) {
            return $query->where($this->getTable().'.branch_id', $branchId);
        }

        // No branch context: return empty result set to prevent data leak
        return $query->whereNull($this->getTable().'.id');
    }

    /**
     * Scope by all branches user has access to
     */
    public function scopeForUserBranches(Builder $query, ?object $user = null): Builder
    {
        $user = $user ?? $this->resolveCurrentUser();

        if (! $user) {
            return $query->whereNull($this->getTable().'.id');
        }

        $branchIds = [];

        if (method_exists($user, 'branches')) {
            if (! $user->relationLoaded('branches')) {
                $user->load('branches');
            }
            $branchIds = $user->branches->pluck('id')->toArray();
        }

        if ($user->branch_id && ! in_array($user->branch_id, $branchIds)) {
            $branchIds[] = $user->branch_id;
        }

        return empty($branchIds)
            ? $query->whereNull($this->getTable().'.id')
            : $query->whereIn($this->getTable().'.branch_id', $branchIds);
    }

    /**
     * Scope by request context branch
     */
    public function scopeInRequestBranch(Builder $query): Builder
    {
        $id = method_exists($this, 'currentBranchId') ? $this->currentBranchId() : null;

        return $id ? $query->where($this->getTable().'.branch_id', $id) : $query;
    }

    /**
     * Resolve current authenticated user
     */
    protected function resolveCurrentUser(): ?object
    {
        // Use BranchContextManager for safe auth resolution
        return \App\Services\BranchContextManager::getCurrentUser();
    }

    /**
     * Check if model belongs to a specific branch
     */
    public function belongsToBranch(int $branchId): bool
    {
        return $this->branch_id === $branchId;
    }

    /**
     * Check if model is accessible by user
     * V22-MED-02 FIX: Query branches relationship directly instead of requiring it to be pre-loaded
     */
    public function isAccessibleByUser(?object $user = null): bool
    {
        $user = $user ?? $this->resolveCurrentUser();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin', 'super-admin'])) {
            return true;
        }

        if ($this->branch_id === $user->branch_id) {
            return true;
        }

        // V22-MED-02 FIX: Check branches relationship via query instead of requiring it to be loaded
        // This ensures correct results even when the relationship isn't pre-loaded
        if (method_exists($user, 'branches')) {
            // If already loaded, use the collection
            if ($user->relationLoaded('branches')) {
                return $user->branches->contains('id', $this->branch_id);
            }

            // Otherwise, query the pivot table directly to avoid loading all branches
            return $user->branches()->where('branches.id', $this->branch_id)->exists();
        }

        return false;
    }
}
