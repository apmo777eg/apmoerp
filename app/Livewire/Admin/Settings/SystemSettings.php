<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class SystemSettings extends Component
{
    public array $rows = [];

    /**
     * Matrix بيانات للـ UI بتاعة الـ Roles & Permissions
     *
     * لكل رول هنخزّن:
     *  - id
     *  - name
     *  - guard
     *  - permissions_by_module => [module => [actions...]]
     */
    public array $rolesMatrix = [];

    /**
     * Flat list لكل الـ permissions (للإظهار فقط).
     *
     * @var array<int,string>
     */
    public array $allPermissions = [];

    /**
     * Map بسيط يربط الشاشات الأساسية بالـ permission بتاعها
     * (نفس اللي في config/screen_permissions.php) عشان نعرضه في UI.
     */
    public array $screenPermissions = [];

    public array $deletedIds = [];

    public function mount(): void
    {

        $user = Auth::user();
        if (! $user || ! $user->can('settings.view')) {
            abort(403);
        }

        $this->loadRows();
        $this->loadRolesAndPermissions();
        $this->loadScreenPermissionsMap();
    }

    protected function loadRows(): void
    {
        $this->rows = SystemSetting::query()
            ->orderBy('setting_group')
            ->orderBy('setting_key')
            ->get()
            ->map(function (SystemSetting $row) {
                return [
                    'id' => $row->getKey(),
                    'key' => $row->setting_key,
                    'value' => $row->value,
                    'group' => $row->setting_group,
                    'is_public' => (bool) $row->is_public,
                ];
            })
            ->all();
    }

    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'key' => '',
            'value' => '',
            'group' => null,
            'is_public' => false,
        ];
    }

    public function removeRow(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $row = $this->rows[$index];
        if (! empty($row['id'])) {
            $this->deletedIds[] = (int) $row['id'];
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function save(): mixed
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.update')) {
            abort(403);
        }

        $this->resetErrorBag();

        // validation خفيفة: لازم key + value على الأقل
        $clean = [];
        $seenKeys = [];
        foreach ($this->rows as $index => $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $value = (string) ($row['value'] ?? '');
            $group = trim((string) ($row['group'] ?? ''));
            $isPublic = (bool) ($row['is_public'] ?? false);

            // Allow skipping completely empty rows
            if ($key === '' && $value === '' && $group === '' && $isPublic === false) {
                continue;
            }

            if ($key === '') {
                $this->addError("rows.$index.key", __('A key is required.'));

                continue;
            }

            if (mb_strlen($key) > 191) {
                $this->addError("rows.$index.key", __('Keys must be 191 characters or fewer.'));

                continue;
            }

            if (isset($seenKeys[$key])) {
                $this->addError("rows.$index.key", __('Duplicate setting key.'));

                continue;
            }

            $seenKeys[$key] = true;

            $clean[] = [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'key' => $key,
                'value' => $value,
                'group' => $group !== '' ? $group : null,
                'is_public' => $isPublic,
            ];
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        // نقرأ الإعدادات القديمة عشان نقدر نسجل الـ changes في الـ audit log
        $before = SystemSetting::query()
            ->get()
            ->keyBy('id')
            ->map(fn (SystemSetting $s) => [
                'id' => $s->id,
                'key' => $s->setting_key,
                'value' => $s->value,
                'group' => $s->setting_group,
                'is_public' => (bool) $s->is_public,
            ])
            ->all();

        DB::transaction(function () use ($clean): void {
            foreach ($clean as $row) {
                if ($row['id']) {
                    /** @var SystemSetting|null $model */
                    $model = SystemSetting::query()->whereKey($row['id'])->first();
                    if (! $model) {
                        continue;
                    }
                    $model->fill([
                        'setting_key' => $row['key'],
                        'value' => $row['value'],
                        'setting_group' => $row['group'],
                        'is_public' => $row['is_public'],
                    ])->save();
                } else {
                    $created = SystemSetting::query()->create([
                        'setting_key' => $row['key'],
                        'value' => $row['value'],
                        'setting_group' => $row['group'],
                        'is_public' => $row['is_public'],
                    ]);
                }
            }

            if (! empty($this->deletedIds)) {
                SystemSetting::query()->whereIn('id', $this->deletedIds)->delete();
            }
        });

        // نقرأ الإعدادات بعد التعديل
        $after = SystemSetting::query()
            ->get()
            ->keyBy('id')
            ->map(fn (SystemSetting $s) => [
                'id' => $s->id,
                'key' => $s->setting_key,
                'value' => $s->value,
                'group' => $s->setting_group,
                'is_public' => (bool) $s->is_public,
            ])
            ->all();

        // نحسب الـ changes
        $changes = [];
        foreach ($after as $id => $row) {
            $beforeRow = $before[$id] ?? null;
            if (! $beforeRow) {
                $changes[] = [
                    'type' => 'created',
                    'key' => $row['key'],
                    'after' => $row,
                ];

                continue;
            }

            if ($beforeRow['value'] !== $row['value']
                || $beforeRow['group'] !== $row['group']
                || $beforeRow['is_public'] !== $row['is_public']) {
                $changes[] = [
                    'type' => 'updated',
                    'key' => $row['key'],
                    'before' => $beforeRow,
                    'after' => $row,
                ];
            }
        }

        foreach ($before as $id => $row) {
            if (! isset($after[$id])) {
                $changes[] = [
                    'type' => 'deleted',
                    'key' => $row['key'],
                    'before' => $row,
                    'after' => null,
                ];
            }
        }

        if (! empty($changes)) {
            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'target_user_id' => null,
                'action' => 'system.settings.updated',
                'meta' => [
                    'changes' => $changes,
                ],
            ]);
        }

        $this->deletedIds = [];
        $this->loadRows();

        session()->flash('status', __('System settings saved successfully.'));

        $this->dispatch('settings-saved');

        $this->redirectRoute('admin.settings.system', navigate: true);
    }

    protected function loadRolesAndPermissions(): void
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        $matrix = [];

        foreach ($roles as $role) {
            $byModule = [];

            foreach ($role->permissions as $perm) {
                $name = (string) $perm->name;
                if ($name === '') {
                    continue;
                }

                // نفصل module.action
                $parts = explode('.', $name, 2);
                $module = $parts[0] ?? 'misc';
                $action = $parts[1] ?? '';

                if (! isset($byModule[$module])) {
                    $byModule[$module] = [];
                }
                $byModule[$module][] = $action !== '' ? $action : $name;
            }

            // sort actions داخل كل module
            foreach ($byModule as $module => $actions) {
                sort($actions);
                $byModule[$module] = $actions;
            }

            ksort($byModule);

            $matrix[] = [
                'id' => $role->getKey(),
                'name' => $role->name,
                'guard' => $role->guard_name,
                'permissions_by_module' => $byModule,
                'permissions_count' => $role->permissions->count(),
            ];
        }

        $this->rolesMatrix = $matrix;
        $this->allPermissions = $permissions->pluck('name')->all();
    }

    protected function loadScreenPermissionsMap(): void
    {
        $this->screenPermissions = [
            [
                'label' => __('Dashboard'),
                'route_name' => 'dashboard',
                'permission' => config('screen_permissions.dashboard', 'dashboard.view'),
            ],
            [
                'label' => __('POS Terminal'),
                'route_name' => 'pos.terminal',
                'permission' => config('screen_permissions.pos.terminal', 'pos.use'),
            ],
            [
                'label' => __('Users'),
                'route_name' => 'admin.users.index',
                'permission' => config('screen_permissions.admin.users.index', 'users.manage'),
            ],
            [
                'label' => __('Branches'),
                'route_name' => 'admin.branches.index',
                'permission' => config('screen_permissions.admin.branches.index', 'branches.view'),
            ],
            [
                'label' => __('System settings'),
                'route_name' => 'admin.settings.system',
                'permission' => config('screen_permissions.admin.settings.system', 'settings.view'),
            ],
            [
                'label' => __('Branch settings'),
                'route_name' => 'admin.settings.branch',
                'permission' => config('screen_permissions.admin.settings.branch', 'settings.branch'),
            ],
            [
                'label' => __('Notifications center'),
                'route_name' => 'notifications.center',
                'permission' => config('screen_permissions.notifications.center', 'system.view-notifications'),
            ],
            [
                'label' => __('Inventory products'),
                'route_name' => 'inventory.products.index',
                'permission' => config('screen_permissions.inventory.products.index', 'inventory.products.view'),
            ],
            [
                'label' => __('HRM reports'),
                'route_name' => 'hrm.reports.dashboard',
                'permission' => config('screen_permissions.hrm.reports.dashboard', 'hr.view-reports'),
            ],
            [
                'label' => __('Rental reports'),
                'route_name' => 'rental.reports.dashboard',
                'permission' => config('screen_permissions.rental.reports.dashboard', 'rental.view-reports'),
            ],
            [
                'label' => __('Audit log'),
                'route_name' => 'admin.logs.audit',
                'permission' => config('screen_permissions.logs.audit', 'logs.audit.view'),
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.settings.system-settings', [
            'rolesMatrix' => $this->rolesMatrix,
            'allPermissions' => $this->allPermissions,
            'screenPermissions' => $this->screenPermissions,
        ]);
    }
}
