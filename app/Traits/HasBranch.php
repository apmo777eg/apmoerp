<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Services\BranchContextManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public static function bootHasBranch(): void
    {
        // Apply global branch scope for multi-tenancy isolation
        static::addGlobalScope(new BranchScope);

        static::creating(function (Model $model): void {
            // Only auto-assign branch_id if:
            // 1. The model doesn't already have a branch_id
            // 2. The model has branch_id in its fillable attributes (table has the column)
            if (! $model->getAttribute('branch_id') && in_array('branch_id', $model->getFillable(), true)) {
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

        return $query;
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

        if (method_exists($user, 'branches') && $user->relationLoaded('branches')) {
            return $user->branches->contains('id', $this->branch_id);
        }

        return false;
    }
}
