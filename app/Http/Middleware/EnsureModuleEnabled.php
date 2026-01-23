<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureModuleEnabled
 *
 * - Checks that the module identified by key is enabled for the current branch.
 * - Also checks that the module itself is active (is_active=true).
 * - Accepts parameter form: module.enabled:{moduleKey}
 *   OR picks from request attribute 'module.key' set by SetModuleContext.
 *
 * Usage alias: 'module.enabled'
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, ?string $moduleKey = null): Response
    {
        /** @var Branch|null $branch */
        $branch = $request->attributes->get('branch');
        $key = $moduleKey ?: (string) $request->attributes->get('module.key', '');

        if (! $branch) {
            return $this->error('Branch context missing.', 422);
        }
        if ($key === '') {
            return $this->error('Module key is required for this route.', 422);
        }

        // Check that:
        // 1. The module exists and is active (is_active=true)
        // 2. The module is enabled for this branch (enabled=true in branch_modules pivot)
        if (! class_exists(BranchModule::class)) {
            // If schema absent, don't block development flows
            return $next($request);
        }

        $isEnabled = BranchModule::query()
            ->where('branch_id', $branch->getKey())
            ->where('enabled', true) // Must be enabled for this branch
            ->where(function ($query) use ($key) {
                // Check module by relationship (module_id) or by key (module_key fallback)
                if (class_exists(Module::class)) {
                    $query->whereHas('module', function ($w) use ($key) {
                        $w->where('module_key', $key)
                            ->where('is_active', true); // Module must also be active
                    });
                } else {
                    // Fallback schema without Module model
                    $query->where('module_key', $key);
                }
            })
            ->exists();

        if (! $isEnabled) {
            return $this->error("Module [$key] is not enabled for this branch.", 403);
        }

        return $next($request);
    }

    protected function error(string $message, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
