<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Services\BranchContextManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetUserBranchContext
 *
 * Sets the branch context based on the authenticated user's branch_id.
 * This ensures that models using HasBranch trait automatically scope
 * queries to the user's branch, preventing cross-branch data leakage.
 *
 * V56-CRITICAL-02 FIX: Now respects session('admin_branch_context') for users
 * with branch switching permission (Super Admin or branches.view-all), ensuring
 * consistency between BranchSwitcher UI and query/write operations.
 *
 * Usage: Add to web middleware group or apply to specific routes
 */
class SetUserBranchContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // V56-CRITICAL-02 FIX: Check if user can switch branches and has an active branch context in session
        $branchId = $this->resolveBranchId($user);

        if ($branchId) {
            // Set branch context in request attributes
            $request->attributes->set('branch_id', $branchId);

            // Also store in service container for easy access in services
            app()->instance('req.branch_id', $branchId);

            // V56-CRITICAL-02 FIX: Set explicit branch context for BranchContextManager
            // This ensures getCurrentBranchId() returns the correct branch for record creation
            BranchContextManager::setBranchContext($branchId);

            // Store branch model in container if available
            $branch = $this->resolveBranch($user, $branchId);
            if ($branch) {
                $request->attributes->set('branch', $branch);
            }
        }

        return $next($request);
    }

    /**
     * Resolve the effective branch ID for the current request.
     * V56-CRITICAL-02 FIX: Respects admin branch context from session when user can switch branches.
     */
    private function resolveBranchId(object $user): ?int
    {
        // Check if user can switch branches (Super Admin or branches.view-all permission)
        $canSwitchBranches = $this->canSwitchBranches($user);

        if ($canSwitchBranches) {
            // V56-CRITICAL-02 FIX: Check session for admin_branch_context set by BranchSwitcher
            $sessionBranchId = session('admin_branch_context');

            if ($sessionBranchId !== null) {
                // Validate the session branch ID is valid and active
                $branchExists = Branch::where('id', $sessionBranchId)
                    ->where('is_active', true)
                    ->exists();

                if ($branchExists) {
                    return (int) $sessionBranchId;
                }

                // Invalid session branch - clear it and fall back to user's branch
                session()->forget('admin_branch_context');
            }
        }

        // Fall back to user's primary branch_id
        if (isset($user->branch_id) && $user->branch_id) {
            return (int) $user->branch_id;
        }

        return null;
    }

    /**
     * Check if user has permission to switch branches.
     */
    private function canSwitchBranches(object $user): bool
    {
        // Check for Super Admin role
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        // Check for branches.view-all permission
        if (method_exists($user, 'can') && $user->can('branches.view-all')) {
            return true;
        }

        return false;
    }

    /**
     * Resolve the branch model for the given branch ID.
     */
    private function resolveBranch(object $user, int $branchId): ?Branch
    {
        // If the resolved branch is user's own branch and it's loaded, use it
        if (isset($user->branch_id) && $user->branch_id === $branchId) {
            if ($user->relationLoaded('branch')) {
                return $user->branch;
            }
            if (method_exists($user, 'branch')) {
                return $user->branch()->first();
            }
        }

        // Otherwise fetch the branch by ID
        return Branch::find($branchId);
    }
}
