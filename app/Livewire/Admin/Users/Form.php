<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role as WebRole;

#[Layout('layouts.app')]
class Form extends Component
{
    use HandlesErrors;

    public ?int $userId = null;

    /**
     * @var array{name:string,email:string,password:string,password_confirmation:string,phone:string|null,username:string|null,branch_id:int|null,is_active:bool,locale:string,timezone:string}
     */
    public array $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'phone' => '',
        'username' => '',
        'branch_id' => null,
        'is_active' => true,
        'locale' => 'ar',
        'timezone' => '',
    ];

    /**
     * @var array<int,array{id:int,name:string}>
     */
    public array $availableRoles = [];

    /**
     * @var array<int,int>
     */
    public array $selectedRoles = [];

    /**
     * Enabled modules for the selected branch
     *
     * @var array<string>
     */
    public array $branchModules = [];

    public function mount(?int $user = null): void
    {
        $authUser = Auth::user();
        if (! $authUser || ! $authUser->can('users.manage')) {
            abort(403);
        }

        $this->userId = $user;

        $this->availableRoles = WebRole::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(fn (WebRole $role) => ['id' => $role->id, 'name' => $role->name])
            ->all();

        if ($this->userId) {
            /** @var User $u */
            $u = User::query()->findOrFail($this->userId);

            $this->form['name'] = $u->name;
            $this->form['email'] = $u->email;
            $this->form['phone'] = $u->phone;
            $this->form['username'] = $u->username;
            $this->form['branch_id'] = $u->branch_id;
            $this->form['is_active'] = (bool) $u->is_active;
            $this->form['locale'] = $u->locale ?? 'ar';
            $this->form['timezone'] = $u->timezone ?? config('app.timezone');

            $this->selectedRoles = $u->roles()
                ->where('guard_name', 'web')
                ->pluck('id')
                ->all();

            // Load branch modules for the user's branch
            $this->loadBranchModules($u->branch_id);
        } else {
            $this->form['timezone'] = config('app.timezone');
        }
    }

    /**
     * Load enabled modules for a branch
     */
    public function loadBranchModules(?int $branchId): void
    {
        if (! $branchId) {
            $this->branchModules = [];

            return;
        }

        $this->branchModules = BranchModule::query()
            ->where('branch_modules.branch_id', $branchId)
            ->where('branch_modules.enabled', true)
            ->pluck('branch_modules.module_key')
            ->all();
    }

    /**
     * Update branch modules when branch changes
     */
    public function updatedFormBranchId($value): void
    {
        $this->loadBranchModules($value ? (int) $value : null);
    }

    protected function rules(): array
    {
        $userId = $this->userId;

        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'form.phone' => ['nullable', 'string', 'max:50'],
            'form.username' => ['nullable', 'string', 'max:100'],
            'form.branch_id' => ['required', 'integer', 'exists:branches,id'],
            'form.is_active' => ['boolean'],
            'form.locale' => ['required', 'string', 'max:5'],
            'form.timezone' => ['required', 'string', 'max:64'],
            'form.password' => $userId
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $authUser = Auth::user();
        if (! $authUser || ! $authUser->can('users.manage')) {
            abort(403);
        }

        $validated = $this->validate();
        $formData = $this->form;
        $userId = $this->userId;
        $selectedRoles = $this->selectedRoles;

        return $this->handleOperation(
            operation: function () use ($formData, $userId, $selectedRoles) {
                if ($userId) {
                    $user = User::query()->findOrFail($userId);
                } else {
                    $user = new User;
                }

                $rolesBefore = $user->exists
                    ? $user->roles()->where('guard_name', 'web')->pluck('name')->all()
                    : [];

                $user->name = $formData['name'];
                $user->email = $formData['email'];
                $user->phone = $formData['phone'] ?: null;
                $user->username = $formData['username'] ?: null;
                $user->branch_id = $formData['branch_id'];
                $user->is_active = $formData['is_active'];
                $user->locale = $formData['locale'];
                $user->timezone = $formData['timezone'];

                if (! empty($formData['password'])) {
                    $user->password = Hash::make($formData['password']);
                }

                $user->save();

                $roles = ! empty($selectedRoles)
                    ? WebRole::query()
                        ->whereIn('id', $selectedRoles)
                        ->where('guard_name', 'web')
                        ->get()
                    : collect();

                $user->syncRoles($roles);

                $rolesAfter = $user->roles()
                    ->where('guard_name', 'web')
                    ->pluck('name')
                    ->all();

                if ($rolesBefore !== $rolesAfter) {
                    AuditLog::query()->create([
                        'user_id' => Auth::id(),
                        'target_user_id' => $user->getKey(),
                        'action' => 'user.roles.updated',
                        'meta' => [
                            'roles_before' => $rolesBefore,
                            'roles_after' => $rolesAfter,
                        ],
                    ]);
                }
            },
            successMessage: __('User saved successfully.'),
            redirectRoute: 'admin.users.index'
        );
    }

    public function render()
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.users.form', [
            'branches' => $branches,
            'availableRoles' => $this->availableRoles,
            'branchModules' => $this->branchModules,
        ]);
    }
}
