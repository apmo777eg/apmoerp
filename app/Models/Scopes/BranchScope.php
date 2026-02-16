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

        // Skip for models that should never be scoped by branch
        if ($this->shouldExcludeModel($model)) {
            return;
        }

        // If the table is missing branch_id, don't crash the whole request.
        //
        // - For truly global tables (no branch_id column by design), we simply don't scope.
        // - For branch-owned tables that *expect* branch_id (branch_id is in $fillable) but the column is missing
        //   (e.g., migration not yet applied), FAIL CLOSED to an empty result set to avoid data leaks.
        if (! $this->hasBranchIdColumn($model)) {
            if (in_array('branch_id', $model->getFillable(), true) && ! app()->runningUnitTests()) {
                $builder->whereRaw('0 = 1');
            }

            return;
        }

        // Prefer an explicit per-request branch context if available (SetUserBranchContext middleware)
        // This enables "view branch perspective" for Super Admin / view-all users.
        $requestBranchId = $this->resolveRequestBranchId();

        if ($requestBranchId !== null) {
            $table = $model->getTable();
            $builder->where("{$table}.branch_id", $requestBranchId);

            return;
        }

        // Get current user safely through BranchContextManager
        $user = BranchContextManager::getCurrentUser();

        // STILL-V9-HIGH-04 FIX: Fail closed for console contexts (queue workers/scheduled jobs) without user
        // Queue workers and scheduled tasks run in console mode but MUST have branch context
        // to prevent cross-branch operations. If no user/branch context is set, fail closed.
        if (! $user) {
            // Check if a branch context was explicitly set via BranchContextManager
            $explicitBranchId = BranchContextManager::getExplicitBranchId();

            if ($explicitBranchId !== null) {
                // An explicit branch context was set (e.g., from a job payload)
                $table = $model->getTable();
                $builder->where("{$table}.branch_id", $explicitBranchId);
                return;
            }

            // V7-CRITICAL-U01 FIX: Fail closed for both web and console contexts when no user
            // Previously, console mode without user returned without applying any filter
            // Now: return empty result set for ALL contexts without authentication/branch context
            // This prevents queue workers and scheduled jobs from operating across all branches
            if (! app()->runningUnitTests()) {
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

        // V8-CRITICAL-N01 FIX: Get accessible branch IDs from context manager
        // null means Super Admin (all branches) - should have already returned above, but handle defensively
        // [] means no access - apply impossible condition
        // [ids...] means specific branches - apply filter
        $accessibleBranchIds = BranchContextManager::getAccessibleBranchIds();

        // Apply the branch filter
        $table = $model->getTable();

        // V8-CRITICAL-N01 FIX: null = Super Admin, don't filter (defensive check, should be handled above)
        if ($accessibleBranchIds === null) {
            return;
        }

        if (count($accessibleBranchIds) === 1) {
            $builder->where("{$table}.branch_id", $accessibleBranchIds[0]);
        } elseif (count($accessibleBranchIds) > 1) {
            $builder->whereIn("{$table}.branch_id", $accessibleBranchIds);
        } else {
            // V8-CRITICAL-N01 FIX: Empty array [] now clearly means "no access" - return empty result set
            // Using a condition that's always false in a database-agnostic way
            $builder->whereNull("{$table}.id")->whereNotNull("{$table}.id");
        }
    }
    /**
     * Resolve an explicit branch id from the current HTTP request context.
     *
     * This is set by SetUserBranchContext middleware (request attribute + container binding).
     * When present, it should take priority over role-based bypass rules so that
     * privileged users (Super Admin / branches.view-all) can "view as branch".
     */
    protected function resolveRequestBranchId(): ?int
    {
        $branchId = null;

        try {
            $request = request();
            if ($request && $request->attributes->has('branch_id')) {
                $branchId = $request->attributes->get('branch_id');
            }
        } catch (\Throwable $e) {
            // Ignore: no request bound (CLI / queue / tests)
        }

        if ($branchId === null && app()->bound('req.branch_id')) {
            $branchId = app('req.branch_id');
        }

        if ($branchId === null) {
            return null;
        }

        $branchId = (int) $branchId;

        return $branchId > 0 ? $branchId : null;
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
            // Dashboard widgets are a global catalog (branch-specific layouts are stored elsewhere)
            // Keeping them unscoped prevents "empty catalog" issues when branch_id is nullable.
            \App\Models\DashboardWidget::class,
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
     * Cache for schema column checks to avoid repeated DB queries
     * @var array<string, bool>
     */
    protected static array $schemaColumnCache = [];

    /**
     * Check if the model has a branch_id column.
     *
     * V10-HIGH-03 FIX: Use schema inspection instead of $fillable to determine if a table
     * has a branch_id column. This prevents branch scoping from being silently disabled
     * when branch_id is omitted from $fillable for valid mass-assignment reasons.
     *
     * The check uses cached schema inspection to avoid performance issues.
     */
    protected function hasBranchIdColumn(Model $model): bool
    {
        $table = $model->getTable();

        // Check cache first to avoid repeated schema queries
        if (isset(self::$schemaColumnCache[$table])) {
            return self::$schemaColumnCache[$table];
        }

        try {
            // Prefer schema inspection (single DB query per table per request, cached)
            $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn($table, 'branch_id');
            self::$schemaColumnCache[$table] = $hasColumn;

            return $hasColumn;
        } catch (\Throwable $e) {
            // If schema check fails (e.g., during migrations), fall back to fillable check.
            // This is a best-effort fallback; apply() will still fail-closed when branch_id is expected but missing.
            $fallback = in_array('branch_id', $model->getFillable(), true);
            self::$schemaColumnCache[$table] = $fallback;

            return $fallback;
        }
    }

/**
     * Clear the schema column cache.
     * Useful for testing or after migrations.
     */
    public static function clearSchemaColumnCache(): void
    {
        self::$schemaColumnCache = [];
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
