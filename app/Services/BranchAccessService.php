<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchAdmin;
use App\Models\Module;
use App\Models\User;
use App\Traits\HandlesServiceErrors;
use Illuminate\Database\Eloquent\Collection;

class BranchAccessService
{
    use HandlesServiceErrors;

    public function getUserBranches(User $user): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                // Super Admin / view-all can see all branches.
                if ($this->isSuperAdmin($user) || BranchContextManager::canViewAllBranches($user)) {
                    return Branch::active()->get();
                }

                // Regular users: union of (primary branch_id) + (active pivot assignments)
                $ids = $user->branches()
                    ->wherePivot('is_active', true)
                    ->pluck('branches.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (isset($user->branch_id) && $user->branch_id !== null) {
                    $ids[] = (int) $user->branch_id;
                }

                $ids = array_values(array_unique(array_filter($ids, fn ($v) => (int) $v > 0)));

                if (empty($ids)) {
                    return new Collection();
                }

                return Branch::active()->whereIn('id', $ids)->get();
            },
            operation: 'getUserBranches',
            context: ['user_id' => $user->id]
        );
    }

    public function getUserActiveBranch(User $user): ?Branch
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                $branches = $this->getUserBranches($user);

                return $branches->first();
            },
            operation: 'getUserActiveBranch',
            context: ['user_id' => $user->id],
            defaultValue: null
        );
    }

    public function canAccessBranch(User $user, int $branchId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $branchId) {
                // Super Admin / view-all bypass
                if ($this->isSuperAdmin($user) || BranchContextManager::canViewAllBranches($user)) {
                    return true;
                }

                // Primary branch_id shortcut
                if (isset($user->branch_id) && (int) $user->branch_id === (int) $branchId) {
                    return true;
                }

                return $user->branches()
                    ->where('branches.id', $branchId)
                    ->wherePivot('is_active', true)
                    ->exists();
            },
            operation: 'canAccessBranch',
            context: ['user_id' => $user->id, 'branch_id' => $branchId],
            defaultValue: false
        );
    }

    public function canManageBranch(User $user, int $branchId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $branchId) {
                if ($this->isSuperAdmin($user)) {
                    return true;
                }

                $branchAdmin = BranchAdmin::where('branch_id', $branchId)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->first();

                return $branchAdmin !== null;
            },
            operation: 'canManageBranch',
            context: ['user_id' => $user->id, 'branch_id' => $branchId],
            defaultValue: false
        );
    }

    public function canManageUsersInBranch(User $user, int $branchId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $branchId) {
                if ($this->isSuperAdmin($user)) {
                    return true;
                }

                $branchAdmin = BranchAdmin::where('branch_id', $branchId)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->first();

                return $branchAdmin?->can_manage_users ?? false;
            },
            operation: 'canManageUsersInBranch',
            context: ['user_id' => $user->id, 'branch_id' => $branchId],
            defaultValue: false
        );
    }

    public function canViewReportsInBranch(User $user, int $branchId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $branchId) {
                if ($this->isSuperAdmin($user)) {
                    return true;
                }

                $branchAdmin = BranchAdmin::where('branch_id', $branchId)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->first();

                return $branchAdmin?->can_view_reports ?? false;
            },
            operation: 'canViewReportsInBranch',
            context: ['user_id' => $user->id, 'branch_id' => $branchId],
            defaultValue: false
        );
    }

    public function canExportFromBranch(User $user, int $branchId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $branchId) {
                if ($this->isSuperAdmin($user)) {
                    return true;
                }

                $branchAdmin = BranchAdmin::where('branch_id', $branchId)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->first();

                return $branchAdmin?->can_export_data ?? false;
            },
            operation: 'canExportFromBranch',
            context: ['user_id' => $user->id, 'branch_id' => $branchId],
            defaultValue: false
        );
    }

    public function setBranchAdmin(int $branchId, int $userId, array $permissions = []): BranchAdmin
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $userId, $permissions) {
                $defaults = [
                    'can_manage_users' => true,
                    'can_manage_roles' => false,
                    'can_view_reports' => true,
                    'can_export_data' => true,
                    'can_manage_settings' => false,
                    'is_primary' => false,
                    'is_active' => true,
                ];

                $data = array_merge($defaults, $permissions);

                return BranchAdmin::updateOrCreate(
                    ['branch_id' => $branchId, 'user_id' => $userId],
                    $data
                );
            },
            operation: 'setBranchAdmin',
            context: ['branch_id' => $branchId, 'user_id' => $userId]
        );
    }

    public function removeBranchAdmin(int $branchId, int $userId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $userId) {
                return BranchAdmin::where('branch_id', $branchId)
                    ->where('user_id', $userId)
                    ->delete() > 0;
            },
            operation: 'removeBranchAdmin',
            context: ['branch_id' => $branchId, 'user_id' => $userId],
            defaultValue: false
        );
    }

    public function getBranchAdmins(int $branchId): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                return BranchAdmin::where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->with('user')
                    ->get();
            },
            operation: 'getBranchAdmins',
            context: ['branch_id' => $branchId]
        );
    }

    public function getBranchModules(int $branchId): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $branch = Branch::findOrFail($branchId);

                return $branch->enabledModules()->get();
            },
            operation: 'getBranchModules',
            context: ['branch_id' => $branchId]
        );
    }

    public function canAccessModule(User $user, int $branchId, string $moduleKey): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $branchId, $moduleKey) {
                if ($this->isSuperAdmin($user)) {
                    return true;
                }

                if (! $this->canAccessBranch($user, $branchId)) {
                    return false;
                }

                $branch = Branch::find($branchId);

                return $branch?->hasModule($moduleKey) ?? false;
            },
            operation: 'canAccessModule',
            context: ['user_id' => $user->id, 'branch_id' => $branchId, 'module_key' => $moduleKey],
            defaultValue: false
        );
    }

    public function enableModuleForBranch(int $branchId, int $moduleId, array $settings = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branchId, $moduleId, $settings) {
                $branch = Branch::findOrFail($branchId);
                $module = Module::findOrFail($moduleId);

                $branch->modules()->syncWithoutDetaching([
                    $moduleId => [
                        'enabled' => true,
                        'settings' => json_encode($settings),
                        'module_key' => $module->module_key,
                    ],
                ]);
            },
            operation: 'enableModuleForBranch',
            context: ['branch_id' => $branchId, 'module_id' => $moduleId]
        );
    }

    public function disableModuleForBranch(int $branchId, int $moduleId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branchId, $moduleId) {
                $branch = Branch::findOrFail($branchId);
                $branch->modules()->updateExistingPivot($moduleId, ['enabled' => false]);
            },
            operation: 'disableModuleForBranch',
            context: ['branch_id' => $branchId, 'module_id' => $moduleId]
        );
    }

    public function updateBranchModuleSettings(int $branchId, int $moduleId, array $settings): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branchId, $moduleId, $settings) {
                $branch = Branch::findOrFail($branchId);
                $branch->modules()->updateExistingPivot($moduleId, [
                    'settings' => json_encode($settings),
                ]);
            },
            operation: 'updateBranchModuleSettings',
            context: ['branch_id' => $branchId, 'module_id' => $moduleId]
        );
    }

    public function assignUserToBranch(int $userId, int $branchId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $branchId) {
                $user = User::findOrFail($userId);
                $user->branches()->syncWithoutDetaching([$branchId]);
            },
            operation: 'assignUserToBranch',
            context: ['user_id' => $userId, 'branch_id' => $branchId]
        );
    }

    public function removeUserFromBranch(int $userId, int $branchId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $branchId) {
                $user = User::findOrFail($userId);
                $user->branches()->detach($branchId);

                BranchAdmin::where('user_id', $userId)
                    ->where('branch_id', $branchId)
                    ->delete();
            },
            operation: 'removeUserFromBranch',
            context: ['user_id' => $userId, 'branch_id' => $branchId]
        );
    }

    public function getUsersInBranch(int $branchId): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $branch = Branch::findOrFail($branchId);

                return $branch->users;
            },
            operation: 'getUsersInBranch',
            context: ['branch_id' => $branchId]
        );
    }

    public function filterQueryByBranch($query, User $user, string $branchColumn = 'branch_id')
    {
        return $this->handleServiceOperation(
            callback: function () use ($query, $user, $branchColumn) {
                // Super admins and users with view-all permission see all data
                if ($this->canViewAllBranches($user)) {
                    return $query;
                }

                $branchIds = $this->getUserBranches($user)->pluck('id')->toArray();

                return $query->whereIn($branchColumn, $branchIds);
            },
            operation: 'filterQueryByBranch',
            context: ['user_id' => $user->id, 'branch_column' => $branchColumn]
        );
    }

    public function scopeForUser($query, User $user, string $branchColumn = 'branch_id')
    {
        return $this->filterQueryByBranch($query, $user, $branchColumn);
    }

    public function getAccessibleModulesForUser(User $user): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                if ($this->isSuperAdmin($user)) {
                    return Module::active()->get();
                }

                $branches = $this->getUserBranches($user);
                $moduleIds = [];

                foreach ($branches as $branch) {
                    $branchModuleIds = $branch->enabledModules()->pluck('modules.id')->toArray();
                    $moduleIds = array_merge($moduleIds, $branchModuleIds);
                }

                return Module::whereIn('id', array_unique($moduleIds))
                    ->active()
                    ->get();
            },
            operation: 'getAccessibleModulesForUser',
            context: ['user_id' => $user->id]
        );
    }

    private function isSuperAdmin(User $user): bool
    {
        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin', 'super-admin']);
    }

    /**
     * Check if user can view all branches data (Super Admin or has branches.view-all permission)
     */
    public function canViewAllBranches(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return method_exists($user, 'can') && $user->can('branches.view-all');
    }
}
