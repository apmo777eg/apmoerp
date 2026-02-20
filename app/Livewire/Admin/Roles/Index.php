<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use App\Services\RoleTemplateService;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;
    #[Url]
    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public bool $showTemplates = false;

    public array $templates = [];

    public ?int $compareRole1 = null;

    public ?int $compareRole2 = null;

    public array $comparison = [];

    public function mount(): void
    {
        // Authorization check - must have roles.manage permission
        $user = auth()->user();
        if (! $user || ! $user->can('roles.manage')) {
            abort(403, __('Unauthorized access to role management'));
        }

        $this->loadTemplates();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $id): void
    {
        $this->authorize('roles.manage');

        $role = Role::findOrFail($id);

        if ($role->name === 'Super Admin') {
            session()->flash('error', __('Cannot delete Super Admin role'));

            return;
        }

        $role->delete();
        session()->flash('success', __('Role deleted successfully'));
    }

    public function cloneRole(int $id): void
    {
        $this->authorize('roles.manage');

        $role = Role::with('permissions')->findOrFail($id);

        $newRole = Role::create([
            'name' => $role->name.' (Copy)',
            'guard_name' => $role->guard_name,
        ]);

        $newRole->syncPermissions($role->permissions);

        session()->flash('success', __('Role cloned successfully'));
    }

    protected function loadTemplates(): void
    {
        $service = app(RoleTemplateService::class);
        $this->templates = $service->getTemplates();
    }

    public function createFromTemplate(string $templateKey): void
    {
        $this->authorize('roles.manage');

        $service = app(RoleTemplateService::class);

        try {
            $service->createFromTemplate($templateKey);
            session()->flash('success', __('Role created from template successfully'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to create role: ').$e->getMessage());
        }
    }

    public function compareRoles(): void
    {
        if (! $this->compareRole1 || ! $this->compareRole2) {
            session()->flash('error', __('Please select two roles to compare'));

            return;
        }

        $role1 = Role::with('permissions')->findOrFail($this->compareRole1);
        $role2 = Role::with('permissions')->findOrFail($this->compareRole2);

        $perms1 = $role1->permissions->pluck('name')->toArray();
        $perms2 = $role2->permissions->pluck('name')->toArray();

        $this->comparison = [
            'role1' => [
                'name' => $role1->name,
                'permissions' => $perms1,
                'count' => count($perms1),
            ],
            'role2' => [
                'name' => $role2->name,
                'permissions' => $perms2,
                'count' => count($perms2),
            ],
            'common' => array_intersect($perms1, $perms2),
            'only_in_role1' => array_diff($perms1, $perms2),
            'only_in_role2' => array_diff($perms2, $perms1),
        ];
    }

    public function getPermissionCoverage(Role $role): array
    {
        $allPermissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        $coverage = [];
        foreach ($allPermissions as $module => $permissions) {
            $modulePerms = $permissions->pluck('name')->toArray();
            $hasPerms = array_intersect($modulePerms, $rolePermissions);

            $coverage[$module] = [
                'total' => count($modulePerms),
                'has' => count($hasPerms),
                'percentage' => count($modulePerms) > 0 ? (count($hasPerms) / count($modulePerms)) * 100 : 0,
            ];
        }

        return $coverage;
    }

    public function render()
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with('permissions')
            ->withCount('permissions', 'users')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        // Add permission coverage to each role
        $rolesWithCoverage = $roles->through(function ($role) {
            $role->permission_coverage = $this->getPermissionCoverage($role);

            return $role;
        });

        return view('livewire.admin.roles.index', [
            'roles' => $rolesWithCoverage,
            'templates' => $this->templates,
            'comparison' => $this->comparison,
        ])->layout('layouts.app', ['title' => __('Role Management')]);
    }
}
