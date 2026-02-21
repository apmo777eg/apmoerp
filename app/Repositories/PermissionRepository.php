<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository extends EloquentBaseRepository implements PermissionRepositoryInterface
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, string $guardName = 'web'): ?Permission
    {
        return $this->query()
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    public function getAllPermissions(string $guardName = 'web'): Collection
    {
        return $this->query()
            ->where('guard_name', $guardName)
            ->orderBy('name')
            ->get();
    }

    public function getPermissionsByModule(string $module): Collection
    {
        return $this->query()
            ->where('name', 'like', "{$module}.%")
            ->orderBy('name')
            ->get();
    }

    public function getGroupedPermissions(): array
    {
        $permissions = $this->getAllPermissions();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'general';

            if (! isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $grouped[$module][] = $permission;
        }

        ksort($grouped);

        return $grouped;
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->query()->with('roles');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (! empty($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        if (! empty($filters['module'])) {
            $query->where('name', 'like', "{$filters['module']}.%");
        }

        // SECURITY (V59-SQLI-01): Prevent SQL injection via orderBy column/direction
        // Also clamp per-page to avoid accidental heavy queries.
        $perPage = min(max((int) $perPage, 1), 100);

        $allowedSortFields = ['id', 'name', 'guard_name', 'created_at', 'updated_at'];
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

    public function syncToRole(Permission $permission, array $roleIds): Permission
    {
        $permission->roles()->sync($roleIds);

        return $permission->fresh(['roles']);
    }

    public function createForModule(string $module, array $actions = ['view', 'create', 'edit', 'delete']): Collection
    {
        $permissions = new Collection;

        foreach ($actions as $action) {
            $name = "{$module}.{$action}";

            $permission = $this->query()
                ->firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['name' => $name, 'guard_name' => 'web']
                );

            $permissions->push($permission);
        }

        return $permissions;
    }
}
