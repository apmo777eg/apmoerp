<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for permission management
        $this->authorize('permissions.view');
        
        $per = min(max($request->integer('per_page', 50), 1), 200);
        $q = Permission::query()->orderBy('name');
        if ($s = $request->input('q')) {
            $q->where('name', 'like', '%'.$s.'%');
        }

        return $this->ok($q->paginate($per));
    }

    public function store(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for permission management
        $this->authorize('permissions.manage');
        
        $data = $this->validate($request, ['name' => ['required', 'string', 'max:190', 'unique:permissions,name']]);

        return $this->ok(Permission::create($data), __('Created'), 201);
    }

    public function destroy(int $id)
    {
        // V57-HIGH-01 FIX: Add authorization for permission management
        $this->authorize('permissions.manage');
        
        Permission::query()->whereKey($id)->delete();

        return $this->ok(null, __('Deleted'));
    }

    public function syncRole(Request $request, int $roleId)
    {
        // V57-HIGH-01 FIX: Add authorization for permission management
        $this->authorize('permissions.manage');
        
        $this->validate($request, ['permissions' => 'array']);
        $role = \Spatie\Permission\Models\Role::findOrFail($roleId);
        $role->syncPermissions($request->input('permissions', []));

        return $this->ok(['role_id' => $roleId], __('Permissions synced'));
    }
}
