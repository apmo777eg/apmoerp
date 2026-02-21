<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Module;
use App\Repositories\Contracts\ModuleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ModuleRepository extends EloquentBaseRepository implements ModuleRepositoryInterface
{
    /**
     * Note: We instantiate the model internally rather than injecting it to avoid
     * circular dependency issues. Since Module has an observer attached and this
     * repository is registered as a singleton, injecting Module via constructor
     * parameter could cause infinite recursion during container resolution.
     */
    public function __construct()
    {
        parent::__construct(new Module());
    }

    public function findBySlug(string $slug): ?Module
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function findByCode(string $code): ?Module
    {
        // DB-first canonical column is `module_key` (modules table has no `code`).
        // Keep the method name for backward compatibility with the repository interface.
        return $this->query()->where('module_key', $code)->first();
    }

    public function getActiveModules(): Collection
    {
        return $this->query()->where('is_active', true)->orderBy('name')->get();
    }

    public function getModulesForBranch(int $branchId): Collection
    {
        return $this->query()
            ->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->query()->with('branches');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    // DB-first canonical column is `module_key` (modules table has no `code`).
                    ->orWhere('module_key', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // SECURITY (V59-SQLI-01): Prevent SQL injection via orderBy column/direction
        // Also clamp per-page to avoid accidental heavy queries.
        $perPage = min(max((int) $perPage, 1), 100);

        $allowedSortFields = ['id', 'name', 'slug', 'module_key', 'is_active', 'created_at', 'updated_at'];
        $sortField = (string) ($filters['sort_field'] ?? 'name');
        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'name';
        }

        $sortDirection = strtolower((string) ($filters['sort_direction'] ?? 'asc'));
        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function getModuleWithFields(int $moduleId): ?Module
    {
        return $this->query()
            ->with('fields')
            ->find($moduleId);
    }

    public function syncBranches(Module $module, array $branchIds): Module
    {
        $module->branches()->sync($branchIds);

        return $module->fresh(['branches']);
    }

    public function deactivate(Module $module): Module
    {
        $module->is_active = false;
        $module->save();

        return $module;
    }

    public function activate(Module $module): Module
    {
        $module->is_active = true;
        $module->save();

        return $module;
    }

    public function getModulePermissions(Module $module): array
    {
        $basePermissions = ['view', 'create', 'edit', 'delete'];
        $permissions = [];

        foreach ($basePermissions as $action) {
            $permissions[] = "{$module->slug}.{$action}";
        }

        return $permissions;
    }
}
