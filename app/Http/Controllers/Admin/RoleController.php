<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for role management
        $this->authorize('roles.view');
        
        $per = min(max($request->integer('per_page', 50), 1), 200);
        $q = Role::query()->where('guard_name', 'web')->orderBy('name');
        if ($s = $request->input('q')) {
            $q->where('name', 'like', '%'.$s.'%');
        }

        return $this->ok($q->paginate($per));
    }

    public function store(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for role management
        $this->authorize('roles.manage');
        
        $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:190',
                'unique:roles,name,NULL,id,guard_name,web',
            ],
        ]);

        $role = Role::create([
            'name' => $request->input('name'),
            'guard_name' => 'web',
        ]);

        return $this->ok($role->toArray(), __('Created'), 201);
    }

    public function update(Request $request, int $id)
    {
        // V57-HIGH-01 FIX: Add authorization for role management
        $this->authorize('roles.manage');
        
        $role = Role::where('guard_name', 'web')->findOrFail($id);

        $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:190',
                'unique:roles,name,'.$id.',id,guard_name,web',
            ],
        ]);

        $role->update(['name' => $request->input('name')]);

        return $this->ok($role->toArray(), __('Updated'));
    }

    public function destroy(int $id)
    {
        // V57-HIGH-01 FIX: Add authorization for role management
        $this->authorize('roles.manage');
        
        Role::where('guard_name', 'web')->whereKey($id)->delete();

        return $this->ok([], __('Deleted'));
    }

    public function syncPermissions(Request $request, Role $role)
    {
        // V57-HIGH-01 FIX: Add authorization for role management
        $this->authorize('roles.manage');
        
        $this->validate($request, [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($request->input('permissions'));

        return $this->ok($role->load('permissions'), __('Permissions synced'));
    }
}
