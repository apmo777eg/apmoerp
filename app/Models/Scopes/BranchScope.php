<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\BranchContextManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * BranchScope - Global scope for multi-tenancy branch isolation
 *
 * This scope automatically filters queries by the authenticated user's branch_id,
 * ensuring data isolation between branches. Super Admins can bypass this filter.
 *
 * IMPORTANT: Uses BranchContextManager to prevent infinite recursion with Auth
 *
 * Usage: Applied automatically via HasBranch trait's bootHasBranch() method
 */
class BranchScope implements Scope
{
    /**
     * Console commands that should skip branch scope entirely.
     * These are safe commands that don't need branch isolation (e.g., migrations, seeders).
     * Queue workers and scheduled tasks should NOT skip branch scope.
     */
    protected const SAFE_CONSOLE_COMMANDS = [
        'migrate',
        'migrate:fresh',
        'migrate:install',
        'migrate:refresh',
        'migrate:reset',
        'migrate:rollback',
        'migrate:status',
        'db:seed',
        'db:wipe',
        'tinker',
        'config:cache',
        'config:clear',
        'cache:clear',
        'cache:forget',
        'route:cache',
        'route:clear',
        'view:cache',
        'view:clear',
        'optimize',
        'optimize:clear',
        'key:generate',
        'storage:link',
        'vendor:publish',
        'package:discover',
        'ide-helper:generate',
        'ide-helper:models',
        'ide-helper:meta',
    ];

    /**
     * Apply the branch scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip scope only for safe console commands (migrations, seeders, etc.)
        // CRITICAL: Queue workers and scheduled tasks MUST apply branch scope
        if (app()->runningInConsole() && ! app()->runningUnitTests() && $this->isSafeConsoleCommand()) {
            return;
        }

        // CRITICAL: Prevent infinite recursion during authentication
        // When auth is being resolved, don't apply scope
        if (BranchContextManager::isResolvingAuth()) {
            return;
        }

        // Skip if the model doesn't have a branch_id column
        if (! $this->hasBranchIdColumn($model)) {
            return;
        }

        // Skip for models that should never be scoped by branch
        if ($this->shouldExcludeModel($model)) {
            return;
        }

        // Get current user safely through BranchContextManager
        $user = BranchContextManager::getCurrentUser();

        // V7-CRITICAL-U01 FIX: Fail closed for non-console contexts when no user
        // Previously, this returned early and applied NO branch filter, causing data leakage
        // Now: return empty result set for non-console contexts without authentication
        if (! $user) {
            // If we're not in console, not in safe console command, and not unit testing,
            // then we're in a web/API request without authentication - fail closed
            if (! app()->runningInConsole() && ! app()->runningUnitTests()) {
                // Return empty result set by adding an impossible condition
                $table = $model->getTable();
                $builder->whereNull("{$table}.id")->whereNotNull("{$table}.id");
            }
            return;
        }

        // Skip scope for Super Admins (they can see all branches)
        if (BranchContextManager::isSuperAdmin($user)) {
            return;
        }

        // Get accessible branch IDs from context manager
        $accessibleBranchIds = BranchContextManager::getAccessibleBranchIds();

        // Apply the branch filter
        $table = $model->getTable();

        if (count($accessibleBranchIds) === 1) {
            $builder->where("{$table}.branch_id", $accessibleBranchIds[0]);
        } elseif (count($accessibleBranchIds) > 1) {
            $builder->whereIn("{$table}.branch_id", $accessibleBranchIds);
        } elseif (count($accessibleBranchIds) === 0) {
            // User has no branch access - return empty result set
            // Using a condition that's always false in a database-agnostic way
            $builder->whereNull("{$table}.id")->whereNotNull("{$table}.id");
        }
    }

    /**
     * Check if the model should be excluded from branch scoping.
     * Some models should never be filtered by branch.
     */
    protected function shouldExcludeModel(Model $model): bool
    {
        // These models are excluded from branch scoping to prevent recursion
        // and maintain referential integrity
        $excludedModels = [
            \App\Models\User::class,
            \App\Models\Branch::class,
            \App\Models\BranchAdmin::class,
            \App\Models\Module::class,
            \App\Models\Permission::class,
            \App\Models\Role::class,
        ];

        foreach ($excludedModels as $excludedModel) {
            if ($model instanceof $excludedModel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the model has a branch_id column.
     *
     * IMPORTANT: Only check fillable attributes, NOT the existence of branch() method.
     * The HasBranch trait adds branch() method to ALL models via BaseModel,
     * but many tables don't actually have a branch_id column (e.g., sale_items,
     * purchase_items, stock_movements). Using method_exists() would cause SQL errors
     * like "Unknown column X.branch_id" for those models.
     */
    protected function hasBranchIdColumn(Model $model): bool
    {
        // Only check if the model has branch_id in fillable attributes
        // This is the reliable indicator that the table has a branch_id column
        $fillable = $model->getFillable();

        return in_array('branch_id', $fillable, true);
    }

    /**
     * Check if the current console command is in the safe list.
     * Safe commands don't need branch isolation (e.g., migrations, seeders).
     */
    protected function isSafeConsoleCommand(): bool
    {
        // Get the current artisan command
        $argv = $_SERVER['argv'] ?? [];

        if (empty($argv)) {
            return false;
        }

        // Find the command name (usually the second argument after 'artisan')
        foreach ($argv as $arg) {
            // Skip 'artisan' and options starting with '-'
            if ($arg === 'artisan' || str_starts_with($arg, '-')) {
                continue;
            }

            // Check if this is a safe command
            foreach (self::SAFE_CONSOLE_COMMANDS as $safeCommand) {
                if ($arg === $safeCommand || str_starts_with($arg, $safeCommand.':')) {
                    return true;
                }
            }

            // Found a command that's not safe
            return false;
        }

        return false;
    }
}
