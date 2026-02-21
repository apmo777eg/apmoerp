<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Services\BranchContextManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureBranchAccess
 *
 * - Ensures the authenticated user can access the current branch.
 * - Accepts Super Admin shortcut (role/permission check if using spatie).
 *
 * Usage alias: 'branch.access'
 */
class EnsureBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        /** @var Branch|int|string|null $branch */
        $branch = $request->attributes->get('branch') ?? $request->route('branch');

        // Some routes bind {branch} as an ID (int) and some as a Branch model.
        // Normalize to a Branch model when possible.
        if ($branch && ! $branch instanceof Branch) {
            $branchId = (int) $branch;
            if ($branchId > 0) {
                $branch = Branch::query()->find($branchId);
            }
        }

        // If a branch route parameter was provided as an ID but wasn't found,
        // fail fast with 404 (instead of silently treating it as "not branch-scoped").
        $rawBranchParam = $request->route('branch');
        if ($rawBranchParam && ! $branch instanceof Branch && is_numeric((string) $rawBranchParam)) {
            return $this->error('Branch not found.', 404);
        }

        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }
        if (! $branch instanceof Branch) {
            // Some endpoints (e.g., file uploads) are authenticated but not branch-scoped.
            return $next($request);
        }

        // Super admin / view-all shortcut
        if (BranchContextManager::canViewAllBranches($user)) {
            return $next($request);
        }

        // Generic relationship checks (adjust to your schema)
        $can = false;

        // 1) via policy (if defined): $user->can('view', $branch)
        if (method_exists($user, 'can') && $user->can('view', $branch)) {
            $can = true;
        }

        // 2) primary branch_id shortcut (common in this project)
        if (! $can && isset($user->branch_id) && (int) $user->branch_id === (int) $branch->getKey()) {
            $can = true;
        }

        // 3) fallback: check user->branches relation (pivot)
        // SECURITY: Respect pivot is_active flag to avoid granting access to inactive branch assignments.
        if (! $can && method_exists($user, 'branches')) {
            $can = $user->branches()
                ->whereKey($branch->getKey())
                ->wherePivot('is_active', true)
                ->exists();
        }

        if (! $can) {
            return $this->error('You are not allowed to access this branch.', 403);
        }

        return $next($request);
    }

    protected function error(string $message, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
