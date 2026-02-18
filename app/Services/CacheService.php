<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheService - Centralized caching layer for ERP modules
 *
 * STATUS: ACTIVE - Core caching infrastructure service
 * PURPOSE: Provides tagged caching for settings, modules, permissions, products, roles, and branches
 * USAGE: Called by controllers/services for performance optimization
 *
 * This service is fully implemented and actively used throughout the application
 * for caching frequently accessed data like settings, permissions, and products.
 */
class CacheService
{
    use HandlesServiceErrors;

    protected const DEFAULT_TTL = 3600;

    protected array $tags = [];

    public function tags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->getCache()->get($key, $default),
            operation: 'get',
            context: ['key' => $key],
            defaultValue: $default
        );
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($key, $value, $ttl) {
                $ttl = $ttl ?? self::DEFAULT_TTL;

                return $this->getCache()->put($key, $value, $ttl);
            },
            operation: 'put',
            context: ['key' => $key],
            defaultValue: false
        );
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->handleServiceOperation(
            callback: function () use ($key, $callback, $ttl) {
                $ttl = $ttl ?? self::DEFAULT_TTL;

                return $this->getCache()->remember($key, $ttl, $callback);
            },
            operation: 'remember',
            context: ['key' => $key]
        );
    }

    public function forget(string $key): bool
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->getCache()->forget($key),
            operation: 'forget',
            context: ['key' => $key],
            defaultValue: false
        );
    }

    public function flush(): bool
    {
        return $this->handleServiceOperation(
            callback: function () {
                if (! empty($this->tags)) {
                    return $this->getCache()->flush();
                }

                return Cache::flush();
            },
            operation: 'flush',
            context: [],
            defaultValue: false
        );
    }

    public function getSettings(?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $key = $branchId ? "settings.branch.{$branchId}" : 'settings.system';

                return $this->tags(['settings'])->remember($key, function () use ($branchId) {
                    if ($branchId) {
                        return \App\Models\Branch::find($branchId)?->settings ?? [];
                    }

                    // DB-first canonical key column is `setting_key` (system_settings table has no `key`).
                    return \App\Models\SystemSetting::pluck('value', 'setting_key')->toArray();
                });
            },
            operation: 'getSettings',
            context: ['branch_id' => $branchId],
            defaultValue: []
        );
    }

    public function getModules(?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $key = $branchId ? "modules.branch.{$branchId}" : 'modules.all';

                return $this->tags(['modules'])->remember($key, function () use ($branchId) {
                    $query = \App\Models\Module::where('is_active', true);

                    if ($branchId) {
                        $query->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
                    }

                    return $query->get()->toArray();
                });
            },
            operation: 'getModules',
            context: ['branch_id' => $branchId],
            defaultValue: []
        );
    }

    public function getPermissions(?int $userId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId) {
                $key = $userId ? "permissions.user.{$userId}" : 'permissions.all';

                return $this->tags(['permissions'])->remember($key, function () use ($userId) {
                    if ($userId) {
                        $user = \App\Models\User::find($userId);

                        return $user?->getAllPermissions()->pluck('name')->toArray() ?? [];
                    }

                    return \Spatie\Permission\Models\Permission::pluck('name')->toArray();
                });
            },
            operation: 'getPermissions',
            context: ['user_id' => $userId],
            defaultValue: []
        );
    }

    public function clearSettingsCache(?int $branchId = null): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $this->tags(['settings'])->flush();
                Log::info('Settings cache cleared', ['branch_id' => $branchId]);
            },
            operation: 'clearSettingsCache',
            context: ['branch_id' => $branchId]
        );
    }

    public function clearModulesCache(): void
    {
        $this->handleServiceOperation(
            callback: function () {
                $this->tags(['modules'])->flush();
                Log::info('Modules cache cleared');
            },
            operation: 'clearModulesCache',
            context: []
        );
    }

    public function clearPermissionsCache(?int $userId = null): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId) {
                $this->tags(['permissions'])->flush();
                Log::info('Permissions cache cleared', ['user_id' => $userId]);
            },
            operation: 'clearPermissionsCache',
            context: ['user_id' => $userId]
        );
    }

    public function clearAllCache(): void
    {
        $this->handleServiceOperation(
            callback: function () {
                Cache::flush();
                Log::info('All cache cleared');
            },
            operation: 'clearAllCache',
            context: []
        );
    }

    public function getRolesWithPermissions(): array
    {
        return $this->handleServiceOperation(
            callback: function () {
                return $this->tags(['roles', 'permissions'])->remember('roles.with_permissions', function () {
                    return \Spatie\Permission\Models\Role::with('permissions')->get()->toArray();
                });
            },
            operation: 'getRolesWithPermissions',
            context: [],
            defaultValue: []
        );
    }

    public function getPermissionsByModule(string $module): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($module) {
                $key = "permissions.module.{$module}";

                return $this->tags(['permissions'])->remember($key, function () use ($module) {
                    return \Spatie\Permission\Models\Permission::where('name', 'like', "{$module}.%")
                        ->orderBy('name')
                        ->get()
                        ->toArray();
                });
            },
            operation: 'getPermissionsByModule',
            context: ['module' => $module],
            defaultValue: []
        );
    }

    public function getProductsForBranch(int $branchId, int $limit = 100): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $limit) {
                $key = "products.branch.{$branchId}.limit.{$limit}";

                return $this->tags(['products'])->remember($key, function () use ($branchId, $limit) {
                    // V21-HIGH-07 Fix: Use 'status' column instead of 'is_active'
                    // The Product model uses 'status' = 'active' (see scopeActive())
                    return \App\Models\Product::where('branch_id', $branchId)
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->limit($limit)
                        ->get()
                        ->toArray();
                }, 1800);
            },
            operation: 'getProductsForBranch',
            context: ['branch_id' => $branchId, 'limit' => $limit],
            defaultValue: []
        );
    }

    public function getProductByIdentifier(string $identifier): ?array
    {
        return $this->handleServiceOperation(
            callback: function () use ($identifier) {
                $key = 'product.lookup.'.md5($identifier);

                return $this->tags(['products'])->remember($key, function () use ($identifier) {
                    $product = \App\Models\Product::where('sku', $identifier)
                        ->orWhere('barcode', $identifier)
                        ->first();

                    return $product?->toArray();
                }, 600);
            },
            operation: 'getProductByIdentifier',
            context: ['identifier' => $identifier],
            defaultValue: null
        );
    }

    public function getAllBranches(): array
    {
        return $this->handleServiceOperation(
            callback: function () {
                return $this->tags(['branches'])->remember('branches.all', function () {
                    return \App\Models\Branch::where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->toArray();
                });
            },
            operation: 'getAllBranches',
            context: [],
            defaultValue: []
        );
    }

    public function clearProductsCache(?int $branchId = null): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $this->tags(['products'])->flush();
                Log::info('Products cache cleared', ['branch_id' => $branchId]);
            },
            operation: 'clearProductsCache',
            context: ['branch_id' => $branchId]
        );
    }

    public function clearRolesCache(): void
    {
        $this->handleServiceOperation(
            callback: function () {
                $this->tags(['roles'])->flush();
                Log::info('Roles cache cleared');
            },
            operation: 'clearRolesCache',
            context: []
        );
    }

    public function clearBranchesCache(): void
    {
        $this->handleServiceOperation(
            callback: function () {
                $this->tags(['branches'])->flush();
                Log::info('Branches cache cleared');
            },
            operation: 'clearBranchesCache',
            context: []
        );
    }

    protected function getCache()
    {
        if (! empty($this->tags) && $this->supportsTags()) {
            $cache = Cache::tags($this->tags);
            $this->tags = [];

            return $cache;
        }

        $this->tags = [];

        return Cache::store();
    }

    protected function supportsTags(): bool
    {
        $driver = config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'array']);
    }

    /**
     * Warm up cache for frequently accessed data
     * Run this on application boot or via scheduler
     */
    public function warmCache(): void
    {
        $this->handleServiceOperation(
            callback: function () {
                // Warm settings cache
                $this->getSettings();

                // Warm all branches
                $this->getAllBranches();

                // Warm modules
                $this->getModules();

                // Warm roles with permissions
                $this->getRolesWithPermissions();

                Log::info('Cache warmed successfully');
            },
            operation: 'warmCache',
            context: []
        );
    }

    /**
     * Warm branch-specific cache
     */
    public function warmBranchCache(int $branchId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $this->getSettings($branchId);
                $this->getModules($branchId);
                $this->getProductsForBranch($branchId);

                Log::info('Branch cache warmed', ['branch_id' => $branchId]);
            },
            operation: 'warmBranchCache',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Get cache statistics for monitoring
     */
    public function getStats(): array
    {
        return $this->handleServiceOperation(
            callback: function () {
                $driver = config('cache.default');

                return [
                    'driver' => $driver,
                    'supports_tags' => $this->supportsTags(),
                    'prefix' => config('cache.prefix'),
                ];
            },
            operation: 'getStats',
            context: [],
            defaultValue: []
        );
    }
}
