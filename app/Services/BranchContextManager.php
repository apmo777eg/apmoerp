<?php

declare(strict_types=1);

namespace App\Services;

/**
 * BranchContextManager - Manages branch context safely without infinite recursion
 *
 * This service provides a safe way to access the current branch context during
 * authentication and query building without causing infinite recursion between
 * Auth and Global Scopes.
 *
 * Key Features:
 * - Prevents recursion during authentication flow
 * - Caches branch context within request lifecycle
 * - Provides safe fallbacks when auth is not available
 */
class BranchContextManager
{
    /**
     * Flag to prevent recursion during authentication
     */
    protected static bool $resolvingAuth = false;

    /**
     * Cached user instance for current request
     */
    protected static ?object $cachedUser = null;

    /**
     * Cached branch IDs for current request
     * V8-CRITICAL-N01 FIX: null now has two meanings:
     * - When $cachedBranchIdsResolved is false: not yet resolved
     * - When $cachedBranchIdsResolved is true: Super Admin (all branches)
     */
    protected static ?array $cachedBranchIds = null;

    /**
     * V8-CRITICAL-N01 FIX: Flag to distinguish "not yet resolved" from "Super Admin (null = all branches)"
     */
    protected static bool $cachedBranchIdsResolved = false;

    /**
     * STILL-V9-HIGH-04 FIX: Explicitly set branch ID for console/queue contexts
     * This allows jobs to specify their branch context via the job payload
     */
    protected static ?int $explicitBranchId = null;

    /**
     * Check if we're currently resolving authentication
     * Used to prevent infinite recursion
     */
    public static function isResolvingAuth(): bool
    {
        return self::$resolvingAuth;
    }

    /**
     * Get the current authenticated user safely
     * Returns null if we're in the middle of authentication to prevent recursion
     */
    public static function getCurrentUser(): ?object
    {
        // Prevent recursion - if we're resolving auth, return cached value
        if (self::$resolvingAuth) {
            return self::$cachedUser;
        }

        // Return cached user if available
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        // Check if auth is available
        if (! function_exists('auth')) {
            return null;
        }

        try {
            // Set flag to prevent recursion
            self::$resolvingAuth = true;

            // Check if user is authenticated
            if (! auth()->check()) {
                self::$resolvingAuth = false;

                return null;
            }

            // Get and cache the user
            self::$cachedUser = auth()->user();
            self::$resolvingAuth = false;

            return self::$cachedUser;
        } catch (\Exception) {
            self::$resolvingAuth = false;

            return null;
        }
    }

    /**
     * Get accessible branch IDs for the current user
     * Returns cached value to prevent repeated queries
     *
     * V8-CRITICAL-N01 FIX: Returns null for Super Admin to indicate "ALL branches"
     * This distinguishes from an empty array [] which means "no access"
     *
     * @return array<int>|null Returns null for Super Admin (all branches), array of IDs for regular users
     */
    public static function getAccessibleBranchIds(): ?array
    {
        // Return cached value if available (including null for Super Admin)
        if (self::$cachedBranchIds !== null || self::$cachedBranchIdsResolved) {
            return self::$cachedBranchIds;
        }

        // Get current user
        $user = self::getCurrentUser();

        if (! $user) {
            self::$cachedBranchIds = [];
            self::$cachedBranchIdsResolved = true;

            return [];
        }

        // V8-CRITICAL-N01 FIX: Check if user is Super Admin (has access to all branches)
        // Return null as sentinel value for "ALL branches" - distinct from [] which means "no access"
        if (self::isSuperAdmin($user)) {
            self::$cachedBranchIds = null;
            self::$cachedBranchIdsResolved = true;

            return null;
        }

        $branchIds = [];

        // Add primary branch
        if (isset($user->branch_id) && $user->branch_id !== null) {
            $branchIds[] = $user->branch_id;
        }

        // Add additional branches from relationship
        // IMPORTANT: We use withoutGlobalScopes() to prevent recursion
        if (method_exists($user, 'branches')) {
            try {
                // Check if branches are already loaded
                if (! $user->relationLoaded('branches')) {
                    // Load branches WITHOUT global scopes to prevent recursion
                    $user->load(['branches' => function ($query) {
                        $query->withoutGlobalScopes();
                    }]);
                }

                $additionalBranches = $user->branches->pluck('id')->toArray();
                $branchIds = array_unique(array_merge($branchIds, $additionalBranches));
            } catch (\Exception) {
                // Silently ignore relationship loading errors
            }
        }

        self::$cachedBranchIds = array_values(array_filter($branchIds));
        self::$cachedBranchIdsResolved = true;

        return self::$cachedBranchIds;
    }

    /**
     * Check if user is a Super Admin
     */
    public static function isSuperAdmin(?object $user): bool
    {
        if (! $user) {
            return false;
        }

        // Check using spatie/laravel-permission's hasAnyRole method
        if (method_exists($user, 'hasAnyRole')) {
            try {
                return $user->hasAnyRole(['Super Admin', 'super-admin']);
            } catch (\Exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get the current branch ID from the authenticated user
     * Returns the primary branch_id of the current user
     *
     * V55-CRITICAL-02/03 FIX: Prefer explicit branch context set by middleware
     * This ensures records are created in the branch the user is actively working in,
     * not necessarily their primary branch.
     */
    public static function getCurrentBranchId(): ?int
    {
        // V55-CRITICAL-02/03 FIX: First check for explicitly set branch context
        // This is set by SetBranchContext middleware when user navigates to a specific branch
        if (self::$explicitBranchId !== null) {
            return self::$explicitBranchId;
        }

        $user = self::getCurrentUser();

        if (! $user) {
            return null;
        }

        // Return user's primary branch ID if set
        if (isset($user->branch_id) && $user->branch_id !== null) {
            return (int) $user->branch_id;
        }

        // Fallback: try to get from accessible branches
        $branchIds = self::getAccessibleBranchIds();
        if (! empty($branchIds)) {
            return $branchIds[0];
        }

        return null;
    }

    /**
     * STILL-V9-HIGH-04 FIX: Get the explicitly set branch ID for console/queue contexts
     * This allows queue workers and scheduled jobs to operate within a specific branch
     *
     * @return int|null The explicitly set branch ID, or null if not set
     */
    public static function getExplicitBranchId(): ?int
    {
        return self::$explicitBranchId;
    }

    /**
     * STILL-V9-HIGH-04 FIX: Set an explicit branch context for console/queue contexts
     * Jobs should call this method with their branch_id from the job payload
     * before executing any branch-scoped queries.
     *
     * Usage in a job:
     * ```php
     * public function handle(): void
     * {
     *     BranchContextManager::setBranchContext($this->branchId);
     *     try {
     *         // ... perform branch-scoped operations
     *     } finally {
     *         BranchContextManager::clearBranchContext();
     *     }
     * }
     * ```
     *
     * @param int $branchId The branch ID to set as the context
     */
    public static function setBranchContext(int $branchId): void
    {
        self::$explicitBranchId = $branchId;
    }

    /**
     * STILL-V9-HIGH-04 FIX: Clear the explicit branch context
     * Should be called after a job completes to prevent context leakage
     */
    public static function clearBranchContext(): void
    {
        self::$explicitBranchId = null;
    }

    /**
     * Clear all cached values
     * Should be called after authentication state changes
     */
    public static function clearCache(): void
    {
        self::$cachedUser = null;
        self::$cachedBranchIds = null;
        self::$cachedBranchIdsResolved = false;
        self::$resolvingAuth = false;
        self::$explicitBranchId = null;
    }

    /**
     * Set the current user manually (for testing)
     */
    public static function setCurrentUser(?object $user): void
    {
        self::$cachedUser = $user;
        self::$cachedBranchIds = null; // Clear branch IDs cache
        self::$cachedBranchIdsResolved = false; // Reset resolved flag
    }
}
