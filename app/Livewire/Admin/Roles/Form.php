<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\Branch;
use App\Models\Module;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    use HandlesErrors;
    use HasMultilingualValidation;

    public ?Role $role = null;

    public bool $editMode = false;

    public string $name = '';

    public array $selectedPermissions = [];

    /**
     * Selected branch for filtering permissions
     */
    public ?int $filterBranchId = null;

    /**
     * Whether to filter permissions by branch modules
     */
    public bool $filterByBranch = false;

    protected function rules(): array
    {
        $unique = $this->editMode ? '|unique:roles,name,'.$this->role->id : '|unique:roles,name';

        return [
            'name' => 'required|string|max:255'.$unique,
            'selectedPermissions' => 'array',
        ];
    }

    public function mount(?Role $role = null): void
    {
        // Authorization check - must have roles.manage permission
        $user = auth()->user();
        if (! $user || ! $user->can('roles.manage')) {
            abort(403, __('Unauthorized access to role management'));
        }

        if ($role && $role->exists) {
            $this->role = $role;
            $this->editMode = true;
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        }
    }

    /**
     * Get available branches for filtering
     */
    public function getBranchesProperty()
    {
        return Branch::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get enabled modules for the selected branch
     */
    public function getEnabledModulesProperty(): array
    {
        if (! $this->filterByBranch || ! $this->filterBranchId) {
            return [];
        }

        $branch = Branch::find($this->filterBranchId);
        if (! $branch) {
            return [];
        }

        return $branch->modules()
            ->wherePivot('enabled', true)
            ->pluck('key')
            ->toArray();
    }

    /**
     * Map permission prefixes to module keys
     */
    protected function getPermissionModuleMapping(): array
    {
        return [
            'dashboard' => 'core',
            'settings' => 'core',
            'users' => 'core',
            'roles' => 'core',
            'branches' => 'core',
            'modules' => 'core',
            'reports' => 'core',
            'logs' => 'core',
            'system' => 'core',
            'pos' => 'pos',
            'sales' => 'sales',
            'purchases' => 'purchases',
            'customers' => 'customers',
            'suppliers' => 'suppliers',
            'inventory' => 'inventory',
            'warehouse' => 'warehouse',
            'expenses' => 'expenses',
            'income' => 'income',
            'accounting' => 'accounting',
            'banking' => 'banking',
            'hrm' => 'hrm',
            'hr' => 'hrm',
            'rental' => 'rental',
            'rentals' => 'rental',
            'manufacturing' => 'manufacturing',
            'fixed-assets' => 'fixed-assets',
            'projects' => 'projects',
            'documents' => 'documents',
            'helpdesk' => 'helpdesk',
            'tickets' => 'helpdesk',
            'media' => 'media',
            'spares' => 'spares',
            'stores' => 'stores',
            'store' => 'stores',
        ];
    }

    /**
     * Check if a permission group should be visible based on branch modules
     */
    public function isGroupVisible(string $group): bool
    {
        if (! $this->filterByBranch || ! $this->filterBranchId) {
            return true;
        }

        $enabledModules = $this->enabledModules;
        $mapping = $this->getPermissionModuleMapping();

        $moduleKey = $mapping[$group] ?? $group;

        // Core permissions are always visible
        if ($moduleKey === 'core') {
            return true;
        }

        return in_array($moduleKey, $enabledModules);
    }

    /**
     * Updated when branch filter changes
     */
    public function updatedFilterBranchId(): void
    {
        // Reset filterByBranch if no branch selected
        if (! $this->filterBranchId) {
            $this->filterByBranch = false;
        }
    }

    /**
     * Select all permissions (respects branch filter)
     */
    public function selectAllPermissions(): void
    {
        $query = Permission::where('guard_name', 'web');

        if ($this->filterByBranch && $this->filterBranchId) {
            $enabledModules = $this->enabledModules;
            $mapping = $this->getPermissionModuleMapping();

            $query->where(function ($q) use ($enabledModules, $mapping) {
                foreach ($mapping as $prefix => $moduleKey) {
                    if ($moduleKey === 'core' || in_array($moduleKey, $enabledModules)) {
                        $q->orWhere('name', 'like', $prefix.'.%');
                    }
                }
            });
        }

        $this->selectedPermissions = $query->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    /**
     * Clear all permission selections
     */
    public function clearAllPermissions(): void
    {
        $this->selectedPermissions = [];
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $user = auth()->user();
        if (! $user || ! $user->can('roles.manage')) {
            abort(403, __('Unauthorized access to role management'));
        }

        $validated = $this->validate();

        if ($this->editMode && $this->role->name === 'Super Admin') {
            session()->flash('error', __('Cannot modify Super Admin role'));

            return null;
        }

        return $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->role->update(['name' => $validated['name']]);
                    $this->role->syncPermissions(Permission::whereIn('id', $this->selectedPermissions)->get());
                } else {
                    $newRole = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
                    $newRole->syncPermissions(Permission::whereIn('id', $this->selectedPermissions)->get());
                }
            },
            successMessage: $this->editMode ? __('Role updated successfully') : __('Role created successfully'),
            redirectRoute: 'admin.roles.index'
        );
    }

    public function render()
    {
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($p) => explode('.', $p->name)[0] ?? 'general');

        return view('livewire.admin.roles.form', [
            'permissions' => $permissions,
            'branches' => $this->branches,
        ])->layout('layouts.app', ['title' => $this->editMode ? __('Edit Role') : __('Add Role')]);
    }
}
