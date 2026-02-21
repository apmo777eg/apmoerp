<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository extends EloquentBaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, string $guardName = 'web'): ?Role
    {
        return $this->query()
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    public function getAllRoles(string $guardName = 'web'): Collection
    {
        return $this->query()
            ->where('guard_name', $guardName)
            ->orderBy('name')
            ->get();
    }

    public function getRolesWithPermissions(): Collection
    {
        return $this->query()->with('permissions')->get();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->query()->with('permissions');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (! empty($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
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

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $role->fresh(['permissions']);
    }

    public function getUsersWithRole(Role $role): Collection
    {
        return $role->users()->get();
    }

    public function createWithPermissions(array $data, array $permissions = []): Role
    {
        /** @var Role $role */
        $role = $this->create($data);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role->fresh(['permissions']);
    }

    public function updateWithPermissions(Role $role, array $data, array $permissions = []): Role
    {
        $this->update($role, $data);
        $role->syncPermissions($permissions);

        return $role->fresh(['permissions']);
    }
}
