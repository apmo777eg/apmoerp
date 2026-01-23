<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Branch extends BaseModel
{
    protected $table = 'branches';

    protected $fillable = [
        'name',
        'name_ar',
        'code',
        'is_active',
        'address',
        'phone',
        'email',
        'timezone',
        'currency',
        'is_main',
        'parent_id',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_main' => 'bool',
        'parent_id' => 'integer',
        'settings' => 'array',
    ];

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')->withTimestamps();
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'branch_modules')
            ->using(BranchModule::class)
            ->withPivot(['enabled', 'settings', 'module_key'])
            ->withTimestamps();
    }

    public function branchModules(): HasMany
    {
        return $this->hasMany(BranchModule::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function branchAdmins(): HasMany
    {
        return $this->hasMany(BranchAdmin::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_admins')
            ->withPivot(['can_manage_users', 'can_manage_roles', 'can_view_reports', 'can_export_data', 'can_manage_settings', 'is_primary', 'is_active'])
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get all rental units for this branch through properties.
     * Used by route model binding with scopeBindings().
     */
    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(RentalUnit::class, Property::class);
    }

    /**
     * Get all tenants for this branch.
     * Used by route model binding with scopeBindings().
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get all rental contracts for this branch.
     * Used by route model binding with scopeBindings().
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    /**
     * Get all rental invoices for this branch through contracts.
     * Used by route model binding with scopeBindings().
     */
    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(RentalInvoice::class, RentalContract::class, 'branch_id', 'contract_id');
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class);
    }

    public function moduleSettings(): HasMany
    {
        return $this->hasMany(ModuleSetting::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function enabledModules()
    {
        return $this->modules()->wherePivot('enabled', true);
    }

    public function hasModule(string $moduleKey): bool
    {
        return $this->modules()
            ->wherePivot('enabled', true)
            ->where('modules.module_key', $moduleKey)
            ->exists();
    }

    public function getPrimaryAdmin(): ?User
    {
        return $this->admins()
            ->wherePivot('is_primary', true)
            ->wherePivot('is_active', true)
            ->first();
    }

    public function isAdminUser(User $user): bool
    {
        return $this->branchAdmins()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function getModuleSetting(int $moduleId, string $key, $default = null)
    {
        return ModuleSetting::getValue($moduleId, $key, $this->id, $default);
    }

    /**
     * Get active employees in this branch
     */
    public function activeEmployees(): HasMany
    {
        return $this->hasMany(User::class)
            ->where('is_active', true);
    }

    /**
     * Check if a user has a specific permission in this branch
     * This method considers both global permissions and branch admin permissions
     */
    public function userHasPermissionInBranch(User $user, string $permission): bool
    {
        // Super Admin has all permissions
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Check if user is a branch admin with specific permissions
        $branchAdmin = $this->branchAdmins()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($branchAdmin) {
            // Map permission names to branch admin capabilities
            $permissionMap = [
                'branch.employees.manage' => 'can_manage_users',
                'branch.reports.view' => 'can_view_reports',
                'branch.settings.manage' => 'can_manage_settings',
            ];

            if (isset($permissionMap[$permission])) {
                $capability = $permissionMap[$permission];

                return $branchAdmin->$capability ?? false;
            }
        }

        // Fall back to standard permission check
        return $user->can($permission);
    }

    /**
     * Get employees count for this branch
     */
    public function getEmployeesCountAttribute(): int
    {
        return $this->activeEmployees()->count();
    }

    /**
     * Check if user belongs to this branch
     */
    public function hasUser(User $user): bool
    {
        return $user->branch_id === $this->id ||
               $this->users()->where('users.id', $user->id)->exists();
    }
}
