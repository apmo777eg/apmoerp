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
     */
    protected static ?array $cachedBranchIds = null;

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
     * @return array<int>
     */
    public static function getAccessibleBranchIds(): array
    {
        // Return cached value if available
        if (self::$cachedBranchIds !== null) {
            return self::$cachedBranchIds;
        }

        // Get current user
        $user = self::getCurrentUser();

        if (! $user) {
            self::$cachedBranchIds = [];

            return [];
        }

        // Check if user is Super Admin (has access to all branches)
        if (self::isSuperAdmin($user)) {
            // Super Admins see all branches - return empty array to signal "no filtering"
            // Note: Empty array in this context means "don't apply branch filter" not "no access"
            self::$cachedBranchIds = [];

            return [];
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
     */
    public static function getCurrentBranchId(): ?int
    {
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
     * Clear all cached values
     * Should be called after authentication state changes
     */
    public static function clearCache(): void
    {
        self::$cachedUser = null;
        self::$cachedBranchIds = null;
        self::$resolvingAuth = false;
    }

    /**
     * Set the current user manually (for testing)
     */
    public static function setCurrentUser(?object $user): void
    {
        self::$cachedUser = $user;
        self::$cachedBranchIds = null; // Clear branch IDs cache
    }
}
