<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for user management
        $this->authorize('users.view');
        
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $rows = User::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%'))
            ->orderByDesc('id')->paginate($per);

        return $this->ok($rows);
    }

    public function store(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for user management
        $this->authorize('users.manage');
        
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['boolean'],
            'roles' => ['sometimes', 'array'],
        ]);
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'] ?? true,
        ]);
        $user->password = Hash::make($data['password']);
        $user->save();
        if (! empty($data['roles']) && method_exists($user, 'syncRoles')) {
            $user->syncRoles($data['roles']);
        }

        return $this->ok($user, __('Created'), 201);
    }

    public function show(User $user)
    {
        // V57-HIGH-01 FIX: Add authorization for user management
        $this->authorize('users.view');
        
        return $this->ok($user);
    }

    public function update(Request $request, User $user)
    {
        // V57-HIGH-01 FIX: Add authorization for user management
        $this->authorize('users.manage');
        
        $data = $this->validate($request, [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'is_active' => ['boolean'],
            'roles' => ['sometimes', 'array'],
        ]);

        if (array_key_exists('password', $data)) {
            if ($data['password'] !== null) {
                $user->password = Hash::make($data['password']);
            }

            unset($data['password']);
        }
        $user->fill($data);
        $user->save();
        if (array_key_exists('roles', $data) && method_exists($user, 'syncRoles')) {
            $user->syncRoles($data['roles']);
        }

        return $this->ok($user, __('Updated'));
    }

    public function activate(User $user)
    {
        // V57-HIGH-01 FIX: Add authorization for user management
        $this->authorize('users.manage');
        
        $user->is_active = true;
        $user->save();

        return $this->ok($user, __('Activated'));
    }

    public function deactivate(User $user)
    {
        // V57-HIGH-01 FIX: Add authorization for user management
        $this->authorize('users.manage');
        
        $user->is_active = false;
        $user->save();
        event(new \App\Events\UserDisabled($user));

        return $this->ok($user, __('Deactivated'));
    }

    public function resetPassword(Request $request, User $user)
    {
        // V57-HIGH-01 FIX: Add authorization for user management
        $this->authorize('users.manage');
        
        $this->validate($request, ['password' => ['required', 'string', 'min:6']]);
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return $this->ok(null, __('Password reset'));
    }
}
