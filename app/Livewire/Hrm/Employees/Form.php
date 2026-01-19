<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Employees;

use App\Models\Branch;
use App\Models\HREmployee;
use App\Models\User;
use App\Services\Contracts\ModuleFieldServiceInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $employeeId = null;

    /**
     * Base employee fields.
     *
     * @var array{code:string,name:string,position:?string,salary:float,is_active:bool,branch_id:int,user_id:?int}
     */
    public array $form = [
        'code' => '',
        'name' => '',
        'position' => '',
        'salary' => 0.0,
        'is_active' => true,
        'branch_id' => 0,
        'user_id' => null,
    ];

    /**
     * Dynamic field schema for HR employees.
     *
     * @var array<int,array<string,mixed>>
     */
    public array $dynamicSchema = [];

    /**
     * Dynamic field values mapped by field key.
     *
     * @var array<string,mixed>
     */
    public array $dynamicData = [];

    /**
     * Select options for linking an employee to a system user.
     *
     * @var array<int,array{id:int,label:string}>
     */
    public array $availableUsers = [];

    public function mount(ModuleFieldServiceInterface $moduleFields, ?int $employee = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('hrm.employees.view')) {
            abort(403);
        }

        $this->employeeId = $employee;
        $this->form['branch_id'] = (int) ($user->branch_id ?? 1);
        $this->form['is_active'] = true;
        $this->form['salary'] = 0.0;

        // Load dynamic schema for hr.employees, scoped to branch
        $this->dynamicSchema = $moduleFields->formSchema('hr', 'employees', $this->form['branch_id']);

        // Load available users for assignment (same branch)
        $this->availableUsers = User::query()
            ->where('branch_id', $this->form['branch_id'])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function (User $u): array {
                $label = $u->name ?: $u->email;

                return [
                    'id' => $u->id,
                    'label' => $label,
                ];
            })
            ->all();

        if ($this->employeeId) {
            /** @var HREmployee $employeeModel */
            $employeeModel = HREmployee::query()->with('user')->findOrFail($this->employeeId);

            $this->form['code'] = (string) $employeeModel->code;
            $this->form['name'] = (string) $employeeModel->name;
            $this->form['position'] = $employeeModel->position ?? '';
            $this->form['salary'] = decimal_float($employeeModel->salary ?? 0);
            $this->form['is_active'] = (bool) ($employeeModel->is_active ?? true);
            $this->form['branch_id'] = (int) ($employeeModel->branch_id ?? $this->form['branch_id']);
            $this->form['user_id'] = $employeeModel->user_id ? (int) $employeeModel->user_id : null;

            $this->dynamicData = (array) ($employeeModel->extra_attributes ?? []);

            // V23-MED-02 FIX: Recompute schema/users for the employee's branch
            // when editing an employee from another branch
            if ($employeeModel->branch_id && $employeeModel->branch_id !== ($user->branch_id ?? 1)) {
                $this->dynamicSchema = $moduleFields->formSchema('hr', 'employees', $employeeModel->branch_id);
                $this->availableUsers = User::query()
                    ->where('branch_id', $employeeModel->branch_id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email'])
                    ->map(function (User $u): array {
                        $label = $u->name ?: $u->email;

                        return [
                            'id' => $u->id,
                            'label' => $label,
                        ];
                    })
                    ->all();
            }
        } else {
            // Defaults for dynamic fields from schema (if any)
            foreach ($this->dynamicSchema as $field) {
                $name = $field['name'] ?? null;
                if (! $name) {
                    continue;
                }

                if (array_key_exists('default', $field)) {
                    $this->dynamicData[$name] = $field['default'];
                } else {
                    $this->dynamicData[$name] = null;
                }
            }
        }
    }

    protected function rules(): array
    {
        return [
            'form.code' => ['required', 'string', 'max:50'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.position' => ['nullable', 'string', 'max:255'],
            'form.salary' => ['required', 'numeric', 'min:0'],
            'form.is_active' => ['boolean'],
            'form.branch_id' => ['required', 'integer', 'exists:branches,id'],
            'form.user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    #[On('dynamic-form-updated')]
    public function handleDynamicFormUpdated(array $data): void
    {
        $this->dynamicData = $data;
    }

    public function save(): mixed
    {
        $user = Auth::user();
        if (! $user || ! $user->can('hrm.employees.assign')) {
            abort(403);
        }

        $this->validate();

        // V24-MED-01 FIX: Save isNew flag before setting employeeId
        // This ensures correct message is shown (created vs updated)
        $isNew = ! $this->employeeId;

        if ($this->employeeId) {
            /** @var HREmployee $employee */
            $employee = HREmployee::query()->findOrFail($this->employeeId);
        } else {
            $employee = new HREmployee;
        }

        $employee->branch_id = (int) $this->form['branch_id'];
        $employee->code = (string) $this->form['code'];
        $employee->name = (string) $this->form['name'];
        $employee->position = $this->form['position'] !== '' ? (string) $this->form['position'] : null;
        $employee->salary = decimal_float($this->form['salary']);
        $employee->is_active = (bool) $this->form['is_active'];
        $employee->user_id = $this->form['user_id'] ? (int) $this->form['user_id'] : null;

        $employee->extra_attributes = $this->dynamicData;

        $employee->save();

        $this->employeeId = $employee->id;

        // V24-MED-01 FIX: Use isNew flag for correct message
        session()->flash(
            'status',
            $isNew
                ? __('Employee created successfully.')
                : __('Employee updated successfully.')
        );

        $this->redirectRoute('app.hrm.employees.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.hrm.employees.form');
    }
}
