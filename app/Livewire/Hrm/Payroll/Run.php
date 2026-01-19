<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Payroll;

use App\Models\HREmployee;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Run extends Component
{
    public ?string $period = null; // e.g. "2024-01"

    public ?int $branchId = null;

    /**
     * Whether to include inactive employees.
     */
    public bool $includeInactive = false;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.payroll.run')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
        if (! $this->period) {
            $this->period = now()->format('Y-m');
        }
    }

    protected function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
            'includeInactive' => ['boolean'],
        ];
    }

    public function runPayroll(): mixed
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.payroll.run')) {
            abort(403);
        }

        $this->validate();

        if (! $this->branchId) {
            session()->flash('error', __('Branch is not set for current user.'));

            return null;
        }

        DB::transaction(function () {
            $employeesQuery = HREmployee::query()
                ->where('branch_id', $this->branchId);

            if (! $this->includeInactive) {
                $employeesQuery->where('is_active', true);
            }

            $employees = $employeesQuery->get();

            foreach ($employees as $employee) {
                // If a payroll for this employee & period already exists, skip it.
                $existing = Payroll::query()
                    ->where('employee_id', $employee->id)
                    ->where('period', $this->period)
                    ->first();

                if ($existing) {
                    continue;
                }

                $basic = (string) ($employee->salary ?? '0');
                $allowances = '0.00';
                $deductions = '0.00';

                // Calculate net salary with bcmath precision
                $netCalc = bcadd($basic, $allowances, 2);
                $net = bcsub($netCalc, $deductions, 2);

                $model = new Payroll;
                $model->employee_id = $employee->id;
                $model->period = $this->period;
                $model->basic = decimal_float($basic);
                $model->allowances = decimal_float($allowances);
                $model->deductions = decimal_float($deductions);
                $model->net = decimal_float($net);
                $model->status = 'draft';
                $model->extra_attributes = [];
                $model->save();
            }
        });

        session()->flash('status', __('Payroll generated for :period', ['period' => $this->period]));

        $this->redirectRoute('app.hrm.payroll.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.hrm.payroll.run');
    }
}
