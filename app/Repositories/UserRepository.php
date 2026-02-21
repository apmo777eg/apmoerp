<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends EloquentBaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    public function findByUsername(string $username): ?User
    {
        return $this->query()->where('username', $username)->first();
    }

    public function getActiveUsers(): Collection
    {
        return $this->query()->where('is_active', true)->get();
    }

    public function getUsersByBranch(int $branchId): Collection
    {
        return $this->query()
            ->where(function (Builder $q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
            })
            ->get();
    }

    public function getUsersWithRoles(): Collection
    {
        return $this->query()->with('roles')->get();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->query()->with(['roles', 'branch']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $filters['role']));
        }

        // SECURITY (V59-SQLI-01): Prevent SQL injection via orderBy column/direction
        // Also clamp per-page to avoid accidental heavy queries.
        $perPage = min(max((int) $perPage, 1), 100);

        $allowedSortFields = ['id', 'name', 'email', 'username', 'branch_id', 'is_active', 'last_login_at', 'created_at', 'updated_at'];
        $sortField = (string) ($filters['sort_field'] ?? 'created_at');
        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        $sortDirection = strtolower((string) ($filters['sort_direction'] ?? 'desc'));
        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function syncRoles(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user->fresh(['roles']);
    }

    public function syncBranches(User $user, array $branchIds): User
    {
        $user->branches()->sync($branchIds);

        return $user->fresh(['branches']);
    }

    public function updateLastLogin(User $user): User
    {
        $user->last_login_at = now();
        $user->save();

        return $user;
    }

    public function deactivate(User $user): User
    {
        $user->is_active = false;
        $user->save();

        return $user;
    }

    public function activate(User $user): User
    {
        $user->is_active = true;
        $user->save();

        return $user;
    }
}
